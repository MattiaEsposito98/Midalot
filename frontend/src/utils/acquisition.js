// Da dove arriva chi visita il sito (Instagram, Google, link con utm_...), per capire
// quali canali portano utenti. Niente dati personali: solo etichette brevi.
//
// - All'arrivo l'origine si legge dai parametri utm_source/utm_medium/utm_campaign
//   oppure, se mancano, dal sito di provenienza (document.referrer).
// - Per la visita resta in sessionStorage (sparisce alla chiusura della scheda).
// - Solo se l'utente ha accettato i cookie analitici si conserva anche 30 giorni in
//   localStorage, cosi' chi torna un altro giorno per registrarsi non perde l'origine.
// - Alla registrazione l'origine viene salvata sull'utente (users.signup_source...).

const SESSION_KEY = "midalot_acq"
const LOCAL_KEY = "midalot_acq_first"
const LOCAL_TTL_MS = 30 * 24 * 60 * 60 * 1000
const MAX_LEN = 40

// Provenienza -> [origine, mezzo]. Il suffisso copre anche i sottodomini (l.instagram.com...).
const REFERRERS = [
  [/(^|\.)instagram\.com$/, ["instagram", "social"]],
  [/(^|\.)(facebook\.com|fb\.com|fb\.me)$/, ["facebook", "social"]],
  [/(^|\.)tiktok\.com$/, ["tiktok", "social"]],
  [/(^|\.)(twitter\.com|x\.com|t\.co)$/, ["x", "social"]],
  [/(^|\.)(youtube\.com|youtu\.be)$/, ["youtube", "social"]],
  [/(^|\.)reddit\.com$/, ["reddit", "social"]],
  [/(^|\.)(telegram\.org|t\.me)$/, ["telegram", "social"]],
  [/(^|\.)google\.[a-z.]+$/, ["google", "organic"]],
  [/(^|\.)(bing\.com|duckduckgo\.com|ecosia\.org)$/, ["motore_di_ricerca", "organic"]],
]

/** Minuscolo, solo lettere, numeri e _ . - , al massimo 40 caratteri. */
export function cleanTag(value) {
  const clean = String(value ?? "")
    .toLowerCase()
    .replace(/[^a-z0-9_.-]/g, "")
    .slice(0, MAX_LEN)
  return clean || null
}

/** Origine dal sito di provenienza; null se e' diretto o se arriva dal nostro stesso sito. */
export function classifyReferrer(referrer, ownHost) {
  if (!referrer) return null

  let host
  try {
    host = new URL(referrer).hostname.toLowerCase()
  } catch {
    return null
  }

  if (!host || host === ownHost || host.endsWith(`.${ownHost}`)) return null

  for (const [pattern, [source, medium]] of REFERRERS) {
    if (pattern.test(host)) return { source, medium }
  }

  return { source: cleanTag(host.replace(/^www\./, "")) || "altro_sito", medium: "referral" }
}

/** Origine di questo arrivo: utm_* se presenti, altrimenti la provenienza. null = diretto. */
export function readAcquisition(search, referrer, ownHost) {
  const params = new URLSearchParams(search || "")
  const source = cleanTag(params.get("utm_source"))

  if (source) {
    return {
      source,
      medium: cleanTag(params.get("utm_medium")),
      campaign: cleanTag(params.get("utm_campaign")),
      utm: true,
    }
  }

  const ref = classifyReferrer(referrer, ownHost)
  return ref ? { source: ref.source, medium: ref.medium, campaign: null, utm: false } : null
}

function safeStorage(kind) {
  try {
    return window[kind]
  } catch {
    return null
  }
}

function readJson(storage, key) {
  try {
    return JSON.parse(storage?.getItem(key) || "null")
  } catch {
    return null
  }
}

/** Da chiamare una volta all'avvio dell'app: registra l'origine della visita se non c'e' gia'. */
export function captureAcquisition() {
  const session = safeStorage("sessionStorage")
  if (!session || session.getItem(SESSION_KEY)) return

  const found = readAcquisition(window.location.search, document.referrer, window.location.hostname)
  if (!found) return

  try {
    session.setItem(SESSION_KEY, JSON.stringify(found))
  } catch {
    // niente da fare
  }
}

/** Solo con il consenso ai cookie analitici: conserva la prima origine per 30 giorni. */
export function persistAcquisition() {
  const local = safeStorage("localStorage")
  const fromSession = readJson(safeStorage("sessionStorage"), SESSION_KEY)
  if (!local || !fromSession) return

  const existing = readJson(local, LOCAL_KEY)
  if (existing && Date.now() - existing.at < LOCAL_TTL_MS) return

  try {
    local.setItem(LOCAL_KEY, JSON.stringify({ ...fromSession, at: Date.now() }))
  } catch {
    // niente da fare
  }
}

/** Origine di questa visita, altrimenti la prima origine ancora valida; null = diretto. */
export function getAcquisition() {
  const session = readJson(safeStorage("sessionStorage"), SESSION_KEY)
  if (session?.source) return session

  const first = readJson(safeStorage("localStorage"), LOCAL_KEY)
  if (first?.source && Date.now() - first.at < LOCAL_TTL_MS) return first

  return null
}

/** Campi da mandare alla registrazione (vuoto se l'origine e' sconosciuta). */
export function acquisitionPayload() {
  const acq = getAcquisition()
  if (!acq) return {}

  return {
    acq_source: acq.source,
    acq_medium: acq.medium || undefined,
    acq_campaign: acq.campaign || undefined,
  }
}

/**
 * Parametri utm della visita corrente da accodare all'indirizzo "ripulito" mandato a Google,
 * cosi' Analytics attribuisce la campagna. Solo gli utm veri e propri (non la provenienza,
 * che Google legge da solo) e solo della visita in corso, mai quelli conservati a lungo.
 */
export function sessionCampaignQuery() {
  const session = readJson(safeStorage("sessionStorage"), SESSION_KEY)
  if (!session?.utm || !session.source) return ""

  const params = new URLSearchParams()
  params.set("utm_source", session.source)
  if (session.medium) params.set("utm_medium", session.medium)
  if (session.campaign) params.set("utm_campaign", session.campaign)

  return `?${params.toString()}`
}
