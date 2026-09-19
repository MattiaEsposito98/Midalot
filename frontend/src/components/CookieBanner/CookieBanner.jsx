import { useEffect } from "react"
import { Link } from "react-router-dom"
import { useCookieConsent } from "../../hooks/useCookieConsent"
import { API_BASE } from "../../service/api"
import { logError } from "../../utils/logger"
import styles from "./CookieBanner.module.css"

function trackConsentEvent(event) {
  fetch(`${API_BASE}/api/cookie-consent/track`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ event }),
  }).catch(logError)
}

function CookieBanner() {
  const { consent, setConsent } = useCookieConsent()

  useEffect(() => {
    if (!consent) {
      trackConsentEvent("shown")
    }
  }, [consent])

  if (consent) return null

  function handleChoice(value) {
    trackConsentEvent(value)
    setConsent(value)
  }

  return (
    <div className={styles.banner} role="dialog" aria-live="polite" aria-label="Consenso cookie">
      <p className={styles.text}>
        Usiamo cookie tecnici necessari al funzionamento del sito. Solo con il tuo consenso usiamo anche
        cookie analitici (Google Analytics) per capire come viene usato Midalot.{" "}
        <Link to="/cookie">Leggi la Cookie Policy</Link>.
      </p>
      <div className={styles.actions}>
        <button type="button" className="btn btn-outline-light btn-sm" onClick={() => handleChoice("rejected")}>
          Rifiuta
        </button>
        <button type="button" className="btn btn-warning btn-sm" onClick={() => handleChoice("accepted")}>
          Accetta
        </button>
      </div>
    </div>
  )
}

export default CookieBanner
