import { useEffect, useState } from "react"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import UserAvatar from "../../components/UserAvatar/UserAvatar"
import UserTitle from "../../components/UserAvatar/UserTitle"
import css from "./Invita.module.css"
import { trackEvent } from "../../utils/analytics"

/**
 * Invita un amico: link personale (copia / WhatsApp / email), premi e amici invitati.
 */
export default function Invita() {
  const [data, setData] = useState(null)
  const [error, setError] = useState("")
  const [copied, setCopied] = useState(false)
  const [email, setEmail] = useState("")
  const [sending, setSending] = useState(false)
  const [message, setMessage] = useState(null)

  useEffect(() => {
    api
      .get("/referrals")
      .then((res) => setData(res.data))
      .catch((err) => {
        logError(err)
        setError("Non riesco a caricare i tuoi inviti. Riprova tra poco.")
      })
  }, [])

  if (error) return <div className="container py-5 text-center">{error}</div>
  if (!data) return <div className="container py-5 text-center">Caricamento...</div>

  const shareText = `Ti sfido su Midalot! Quiz, minigiochi e sfide tra amici. Iscriviti con il mio invito e ricevi ${data.welcome_coins} monete di benvenuto: ${data.link}`

  async function copy() {
    try {
      await navigator.clipboard.writeText(data.link)
      trackEvent("share", { method: "copy_link", content_type: "invite_friend" })
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    } catch {
      window.prompt("Copia il tuo link:", data.link)
    }
  }

  async function sendEmail(e) {
    e.preventDefault()
    setSending(true)
    setMessage(null)
    try {
      const res = await api.post("/referrals/email", { email })
      trackEvent("share", { method: "email", content_type: "invite_friend" })
      setMessage({ ok: true, text: res.data.message })
      setEmail("")
    } catch (err) {
      logError(err)
      setMessage({ ok: false, text: err?.response?.data?.message || err?.response?.data?.errors?.email?.[0] || "Invio non riuscito." })
    } finally {
      setSending(false)
    }
  }

  return (
    <div className={`container ${css.page}`}>
      <h1 className={css.title}>
        <i className="bi bi-people-fill"></i> Invita un amico
      </h1>
      <p className={css.subtitle}>Porta i tuoi amici su Midalot: più siete, più sono belle le sfide. E guadagni monete!</p>

      <section className={css.card}>
        <h2 className={css.cardTitle}>Come funziona</h2>
        <ol className={css.steps}>
          <li>Condividi il tuo link personale con WhatsApp, email o dove vuoi.</li>
          <li>
            Il tuo amico si iscrive con il tuo link e riceve <strong>{data.welcome_coins} monete di benvenuto</strong>.
          </li>
          {data.level_rewards.map((r) => (
            <li key={r.level}>
              Quando arriva al <strong>livello {r.level}</strong> tu ricevi <strong>{r.coins} monete</strong>.
            </li>
          ))}
          <li>
            Porta {data.ambassador_friends} amici al livello {data.level_rewards[0]?.level} e ricevi il titolo <UserTitle title="Ambasciatore" />.
          </li>
        </ol>
        <p className={css.note}>
          I premi arrivano quando l&apos;amico gioca davvero (massimo {data.monthly_cap} amici premiati al mese).
        </p>
      </section>

      <section className={css.card}>
        <h2 className={css.cardTitle}>Il tuo link</h2>
        <div className={css.linkRow}>
          <input className={css.linkInput} value={data.link} readOnly onFocus={(e) => e.target.select()} aria-label="Il tuo link di invito" />
          <button type="button" className={css.copyBtn} onClick={copy}>
            <i className={`bi ${copied ? "bi-check2" : "bi-clipboard"}`}></i> {copied ? "Copiato!" : "Copia"}
          </button>
        </div>
        <div className={css.shareRow}>
          <a className={css.whatsapp} href={`https://wa.me/?text=${encodeURIComponent(shareText)}`} target="_blank" rel="noopener noreferrer" onClick={() => trackEvent("share", { method: "whatsapp", content_type: "invite_friend" })}>
            <i className="bi bi-whatsapp"></i> Invia su WhatsApp
          </a>
          {navigator.share && (
            <button type="button" className={css.shareBtn} onClick={() => navigator.share({ title: "Midalot", text: shareText }).catch(() => {})}>
              <i className="bi bi-share-fill"></i> Altre app
            </button>
          )}
        </div>

        <form className={css.emailRow} onSubmit={sendEmail}>
          <input
            type="email"
            className={css.linkInput}
            placeholder="Email del tuo amico"
            value={email}
            maxLength={255}
            onChange={(e) => setEmail(e.target.value)}
            required
            aria-label="Email del tuo amico"
          />
          <button type="submit" className={css.copyBtn} disabled={sending}>
            <i className="bi bi-envelope-fill"></i> {sending ? "Invio..." : "Invita"}
          </button>
        </form>
        {message && <div className={message.ok ? css.ok : css.err}>{message.text}</div>}
        <p className={css.note}>L&apos;indirizzo email serve solo per questo invito: non lo salviamo e non lo usiamo per altro.</p>
      </section>

      <section className={css.card}>
        <div className="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
          <h2 className={css.cardTitle}>I tuoi amici ({data.friends.length})</h2>
          <span className={css.earned}>
            <i className="bi bi-coin"></i> {data.coins_earned} monete guadagnate
          </span>
        </div>
        {data.friends.length === 0 ? (
          <p className={css.note}>Non hai ancora invitato nessuno. Inizia con WhatsApp: è il modo più veloce!</p>
        ) : (
          <div className={css.friends}>
            {data.friends.map((f) => (
              <div key={f.nickname} className={css.friend}>
                <UserAvatar url={f.avatar_url} frame={f.avatar_frame} name={f.nickname} size={40} />
                <div className={css.friendName}>
                  <strong>{f.nickname}</strong> <UserTitle title={f.avatar_title} />
                  <div className={css.note}>{f.verified ? `Livello ${f.level}` : "Deve ancora confermare l'email"}</div>
                </div>
                <span className={css.friendCoins}>{f.coins > 0 ? `+${f.coins}` : "—"}</span>
              </div>
            ))}
          </div>
        )}
      </section>
    </div>
  )
}
