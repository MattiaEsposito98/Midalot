import { useState } from "react"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import styles from "./Sfide.module.css"

/**
 * Link della sfida per un amico non registrato: WhatsApp, copia, email.
 */
export default function GuestShare({ challengeId, url, guestPlayed }) {
  const [copied, setCopied] = useState(false)
  const [email, setEmail] = useState("")
  const [sending, setSending] = useState(false)
  const [message, setMessage] = useState(null)

  const text = `Ti sfido a un quiz su Midalot! ⚔️ Gioca subito, senza registrarti: ${url}`

  async function copy() {
    try {
      await navigator.clipboard.writeText(url)
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
        <div className={styles.meta}>
          Il risultato arriva quando si registra: a quel punto ricevete entrambi monete extra.
        </div>
      </div>
    )
  }

  return (
    <div className={styles.sharePanel}>
      <strong>
        <i className="bi bi-send-fill"></i> Manda la sfida al tuo amico
      </strong>
      <div className={styles.meta}>
        Gioca subito senza registrarsi. Per vedere chi ha vinto si registra, e così ricevete entrambi monete extra.
      </div>
      <div className={styles.shareRow}>
        <a className={styles.whatsapp} href={`https://wa.me/?text=${encodeURIComponent(text)}`} target="_blank" rel="noopener noreferrer">
          <i className="bi bi-whatsapp"></i> WhatsApp
        </a>
        <button type="button" className={styles.membersToggle} onClick={copy}>
          <i className={`bi ${copied ? "bi-check2" : "bi-link-45deg"}`}></i> {copied ? "Copiato!" : "Copia link"}
        </button>
      </div>
      <form className="d-flex gap-2" onSubmit={send}>
        <input
          type="email"
          className="form-control form-control-sm"
          placeholder="oppure la sua email"
          value={email}
          maxLength={255}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        <button type="submit" className="btn btn-sm btn-primary" disabled={sending}>
          {sending ? "Invio..." : "Invia"}
        </button>
      </form>
      {message && <div className={`small mt-1 ${message.ok ? "text-success" : "text-danger"}`}>{message.text}</div>}
    </div>
  )
}
