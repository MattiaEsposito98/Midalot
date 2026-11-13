import { useState } from "react"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import styles from "./Sfide.module.css"
import { trackEvent } from "../../utils/analytics"

/**
 * Link della sfida per un amico non registrato: WhatsApp, copia, email.
 * Il sito sa che l'amico e' stato invitato solo se l'invito parte via email:
 * con WhatsApp o "copia link" il messaggio lo manda chi sfida, quindi va detto chiaro.
 */
export default function GuestShare({ challengeId, url, guestPlayed, invited: invitedByEmail, onInvited }) {
  const [copied, setCopied] = useState(false)
  const [email, setEmail] = useState("")
  const [sending, setSending] = useState(false)
  const [message, setMessage] = useState(null)

  const text = `Ti sfido a un quiz su Midalot! ⚔️ Gioca subito, senza registrarti: ${url}`

  async function copy() {
    try {
      await navigator.clipboard.writeText(url)
      trackEvent("share", { method: "copy_link", content_type: "guest_challenge" })
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    } catch {
      window.prompt("Copia il link della sfida:", url)
    }
  }

  async function send(e) {
    e.preventDefault()
    setSending(true)
    setMessage(null)
    try {
      const res = await api.post(`/challenges/${challengeId}/guest-email`, { email })
      setMessage({ ok: true, text: res.data.message })
      setEmail("")
      trackEvent("share", { method: "email", content_type: "guest_challenge" })
      onInvited?.()
    } catch (err) {
      logError(err)
      setMessage({ ok: false, text: err?.response?.data?.message || err?.response?.data?.errors?.email?.[0] || "Invio non riuscito." })
    } finally {
      setSending(false)
    }
  }

  if (guestPlayed) {
    return (
      <div className={styles.sharePanel}>
        <strong>
          <i className="bi bi-check-circle-fill text-success"></i> Il tuo amico ha giocato!
        </strong>
        <div className={styles.meta}>Il risultato arriva quando si registra: a quel punto ricevete entrambi monete extra.</div>
      </div>
    )
  }

  return (
    <div className={styles.sharePanel}>
      <strong>
        <i className="bi bi-send-fill"></i> Manda la sfida al tuo amico
      </strong>

      {invitedByEmail ? (
        <div className={`${styles.shareNotice} ${styles.shareNoticeOk}`}>
          <i className="bi bi-envelope-check-fill"></i> <strong>Invito inviato via email.</strong> Il tuo amico ha ricevuto il link e ha
          tempo fino alla scadenza della sfida per giocare.
        </div>
      ) : (
        <div className={`${styles.shareNotice} ${styles.shareNoticeWarn}`}>
          <i className="bi bi-exclamation-triangle-fill"></i>
          <span>
            <strong>Il tuo amico non è ancora stato invitato.</strong> La sfida è creata, ma lui non sa nulla finché non gli mandi il link:
            invialo su WhatsApp (o copialo) oppure fallo partire via email da qui sotto.
          </span>
        </div>
      )}

      {!invitedByEmail && (
      <ol className={styles.shareSteps}>
        <li>
          <strong>WhatsApp o copia link:</strong> il messaggio lo mandi tu, quindi il sito non può sapere se l&apos;hai inviato (lo stato
          resta &quot;Da invitare&quot; finché il tuo amico non gioca).
        </li>
        <li>
          <strong>Email:</strong> la mandiamo noi al suo indirizzo e lo stato diventa subito <strong>&quot;Invitato&quot;</strong>.
        </li>
      </ol>
      )}

      <div className={styles.shareRow}>
        <a className={styles.whatsapp} href={`https://wa.me/?text=${encodeURIComponent(text)}`} target="_blank" rel="noopener noreferrer" onClick={() => trackEvent("share", { method: "whatsapp", content_type: "guest_challenge" })}>
          <i className="bi bi-whatsapp"></i> Invia su WhatsApp
        </a>
        <button type="button" className={styles.membersToggle} onClick={copy}>
          <i className={`bi ${copied ? "bi-check2" : "bi-link-45deg"}`}></i> {copied ? "Copiato!" : "Copia link"}
        </button>
      </div>

      <form className="d-flex gap-2" onSubmit={send}>
        <input
          type="email"
          className="form-control form-control-sm"
          placeholder="Email del tuo amico"
          value={email}
          maxLength={255}
          onChange={(e) => setEmail(e.target.value)}
          required
          aria-label="Email del tuo amico"
        />
        <button type="submit" className="btn btn-sm btn-primary" disabled={sending}>
          <i className="bi bi-envelope-fill"></i> {sending ? "Invio..." : "Invia email"}
        </button>
      </form>
      {message && <div className={`small mt-1 ${message.ok ? "text-success" : "text-danger"}`}>{message.text}</div>}

      <div className={styles.meta}>Quando si registra ricevete entrambi monete extra. L&apos;email serve solo per questo invito e non viene salvata.</div>
    </div>
  )
}
