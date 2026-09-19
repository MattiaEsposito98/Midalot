import { Link } from "react-router-dom"
import { useCookieConsent } from "../../hooks/useCookieConsent"
import styles from "./CookieBanner.module.css"

function CookieBanner() {
  const { consent, setConsent } = useCookieConsent()

  if (consent) return null

  return (
    <div className={styles.banner} role="dialog" aria-live="polite" aria-label="Consenso cookie">
      <p className={styles.text}>
        Usiamo cookie tecnici necessari al funzionamento del sito. Solo con il tuo consenso usiamo anche
        cookie analitici (Google Analytics) per capire come viene usato Midalot.{" "}
        <Link to="/cookie">Leggi la Cookie Policy</Link>.
      </p>
      <div className={styles.actions}>
        <button type="button" className="btn btn-outline-light btn-sm" onClick={() => setConsent("rejected")}>
          Rifiuta
        </button>
        <button type="button" className="btn btn-warning btn-sm" onClick={() => setConsent("accepted")}>
          Accetta
        </button>
      </div>
    </div>
  )
}

export default CookieBanner
