const GA_ID = import.meta.env.VITE_GA_MEASUREMENT_ID

export function initAnalytics() {
  if (!GA_ID || typeof window === "undefined") return

  window.dataLayer = window.dataLayer || []
  window.gtag = function gtag() {
    window.dataLayer.push(arguments)
  }
  window.gtag("js", new Date())
  window.gtag("config", GA_ID, { send_page_view: false })

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
  const location = window.location.href
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
      page_path: path,
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
