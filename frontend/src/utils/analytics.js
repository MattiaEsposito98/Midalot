import { sessionCampaignQuery } from "./acquisition"

const GA_ID = import.meta.env.VITE_GA_MEASUREMENT_ID

// Percorsi con segreti nel percorso stesso: il pezzo segreto si sostituisce.
const SECRET_PATHS = [[/^\/sfida-amico\/[^/]+/, "/sfida-amico/:token"]]

/**
 * Percorso da mandare a Google: mai la query string (token di reset, email,
 * codici d'invito...) e mai i token dentro il percorso. Google vieta di
 * inviargli dati personali e questi link danno accesso a qualcosa.
 */
export function safePath(pathname) {
  let path = String(pathname || "/").split("?")[0].split("#")[0] || "/"
  for (const [pattern, replacement] of SECRET_PATHS) {
    path = path.replace(pattern, replacement)
  }
  return path
}

// All'indirizzo ripulito si accodano solo gli utm_* della visita (non token, email o codici):
// Google li legge da page_location per attribuire la campagna (es. Instagram / bio).
function safeLocation(path) {
  return window.location.origin + path + sessionCampaignQuery()
}

/**
 * Indirizzo "ripulito" per tutto cio' che Google invia da qui in avanti:
 * senza questo gtag allega a ogni evento l'URL completo della pagina.
 */
function setSafePage(path) {
  window.gtag("set", { page_location: safeLocation(path), page_path: path })
}

export function initAnalytics() {
  if (!GA_ID || typeof window === "undefined") return

  window.dataLayer = window.dataLayer || []
  window.gtag = function gtag() {
    window.dataLayer.push(arguments)
  }
  window.gtag("js", new Date())
  const startPath = safePath(window.location.pathname)
  setSafePage(startPath)
  window.gtag("config", GA_ID, { send_page_view: false, page_location: safeLocation(startPath), page_path: startPath })

  const script = document.createElement("script")
  script.async = true
  script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`
  document.head.appendChild(script)
}

// Il titolo di molte pagine arriva dopo il primo rendering (es. il nome della
// categoria si conosce solo quando i dati sono caricati): se la visualizzazione
// partisse subito, Analytics registrerebbe il titolo della pagina precedente.
const TITLE_MIN_WAIT_MS = 1200
const TITLE_SETTLE_MS = 400
const TITLE_MAX_WAIT_MS = 3000

/**
 * Registra la visualizzazione di pagina quando il titolo si e' assestato:
 * dopo almeno 1,2 s e 400 ms senza modifiche del titolo, al massimo dopo 3 s.
 * Ritorna la funzione che annulla l'invio, da usare come pulizia dell'effetto
 * quando si cambia pagina.
 */
export function trackPageview(path) {
  if (!GA_ID || typeof window === "undefined" || typeof window.gtag !== "function") return () => {}

  const startedAt = Date.now()
  const cleanPath = safePath(path)
  const location = safeLocation(cleanPath)
  setSafePage(cleanPath) // subito: anche gli eventi di questa pagina usano l'indirizzo ripulito
  let settleTimer = null
  let observer = null

  function stop() {
    observer?.disconnect()
    clearTimeout(settleTimer)
    clearTimeout(maxTimer)
  }

  function send() {
    stop()

    window.gtag("event", "page_view", {
      page_path: cleanPath,
      page_location: location,
      page_title: document.title,
    })
  }

  function schedule() {
    clearTimeout(settleTimer)
    const delay = Math.max(TITLE_SETTLE_MS, TITLE_MIN_WAIT_MS - (Date.now() - startedAt))
    settleTimer = setTimeout(send, delay)
  }

  const maxTimer = setTimeout(send, TITLE_MAX_WAIT_MS)
  schedule()

  const titleTag = document.querySelector("title")
  if (titleTag && typeof MutationObserver !== "undefined") {
    observer = new MutationObserver(schedule)
    observer.observe(titleTag, { childList: true, characterData: true, subtree: true })
  }

  return stop
}

/**
 * Evento di conversione (solo se l'utente ha accettato i cookie analitici:
 * senza consenso gtag non esiste e non parte nulla). Nomi in minuscolo con
 * underscore; sign_up, login e share sono eventi raccomandati da GA4.
 * Per vederli come "eventi chiave" vanno segnati in Analytics
 * (Amministrazione -> Eventi -> "Segna come evento chiave").
 */
export function trackEvent(name, params = {}) {
  if (!GA_ID || typeof window === "undefined" || typeof window.gtag !== "function") return

  window.gtag("event", name, params)
}
