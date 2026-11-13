import { useEffect, useState } from "react"
import { Link, useParams } from "react-router-dom"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import UserAvatar from "../../components/UserAvatar/UserAvatar"
import SfidaPlay from "./SfidaPlay"
import { readGuestKey } from "../../utils/guestChallenge"
import styles from "./Sfide.module.css"
import { trackEvent } from "../../utils/analytics"

function isLoggedIn() {
  try {
    return !!localStorage.getItem("token")
  } catch {
    return false
  }
}

/**
 * Sfida ricevuta da un amico, per chi non e' (ancora) registrato:
 * invito -> partita -> punteggio -> "registrati per vedere chi ha vinto".
 */
export default function SfidaAmico() {
  const { token } = useParams()
  const [info, setInfo] = useState(null)
  const [error, setError] = useState("")
  const [playing, setPlaying] = useState(false)
  const [result, setResult] = useState(null)

  useEffect(() => {
    api
      .get(`/guest-challenges/${token}`, { params: { key: readGuestKey(token) || undefined } })
      .then((res) => {
        trackEvent("guest_challenge_view", {})
        setInfo(res.data)
        if (res.data.result) setResult(res.data.result)
      })
      .catch((err) => {
        logError(err)
        setError(err?.response?.status === 404 ? "Questa sfida non esiste." : "Non riesco a caricare la sfida. Riprova tra poco.")
      })
  }, [token])

  if (playing && !result) {
    return <SfidaPlay guestToken={token} onGuestFinished={(r) => setResult(r)} />
  }

  if (error) {
    return (
      <div className={`container ${styles.page}`}>
        <div className={styles.panel}>
          <p>{error}</p>
          <Link to="/" className="btn btn-primary">
            Scopri Midalot
          </Link>
        </div>
      </div>
    )
  }

  if (!info) return <div className="container py-5 text-center">Caricamento...</div>

  const challenger = info.challenger.nickname

  // Fine partita: solo il proprio punteggio, il resto dopo la registrazione.
  if (result) {
    const registerUrl = `/register?ref=${encodeURIComponent(result.ref)}&sfida=${encodeURIComponent(token)}`
    return (
      <div className={`container ${styles.page}`}>
        <div className={`${styles.panel} text-center`}>
          <div style={{ fontSize: "2.4rem" }}>🎉</div>
          <h1 className={styles.title}>Hai finito la sfida!</h1>
          <p className="mt-3 mb-1">Il tuo punteggio</p>
          <div className={styles.playerScore}>{formatQuizScore(result.score)}</div>
          <p className={styles.meta}>
            {result.correct_answers} risposte giuste su {result.total_questions}
          </p>

          <div className={`${styles.hint} ${styles.hintAll} text-start mt-3`}>
            <i className="bi bi-trophy-fill"></i>
            <div>
              <strong>Chi ha vinto tra te e {challenger}?</strong> Registrati gratis per scoprirlo e rivedere le risposte giuste.
              <br />
              Iscrivendoti ricevi <strong>{result.welcome_coins} monete di benvenuto</strong>, e anche {challenger} riceve
              monete extra. 🪙
            </div>
          </div>

          <Link to={registerUrl} className="btn btn-warning btn-lg fw-bold mt-2" onClick={() => trackEvent("guest_challenge_register_click", {})}>
            Registrati e scopri il risultato
          </Link>
          <p className={`${styles.meta} mt-3`}>La tua partita resta salvata: la ritrovi nel tuo account appena ti registri.</p>
        </div>
      </div>
    )
  }

  return (
    <div className={`container ${styles.page}`}>
      <div className={`${styles.panel} text-center`}>
        <UserAvatar url={info.challenger.avatar_url} frame={info.challenger.avatar_frame} name={challenger} size={72} className="mx-auto" />
        <h1 className={`${styles.title} mt-2`}>{challenger} ti sfida! ⚔️</h1>
        <p className={styles.subtitle}>
          {info.total_questions} domande a tempo{info.categories?.length ? ` su ${info.categories.join(", ")}` : ""}. Più sei veloce
          a rispondere giusto, più punti fai.
        </p>

        {isLoggedIn() ? (
          <div className={`${styles.hint} text-start mt-3`}>
            <i className="bi bi-info-circle-fill"></i>
            <div>
              Sei già su Midalot! Questo link è per chi non è ancora iscritto: chiedi a {challenger} di sfidarti dalla pagina{" "}
              <Link to="/sfide">Sfide</Link>.
            </div>
          </div>
        ) : info.playable ? (
          <>
            <div className={`${styles.hint} text-start mt-3`}>
              <i className="bi bi-gift-fill"></i>
              <div>
                Puoi giocare subito, <strong>senza registrarti</strong>. Alla fine, iscrivendoti gratis, scopri chi ha vinto e
                ricevi <strong>{info.welcome_coins} monete di benvenuto</strong>.
              </div>
            </div>
            <button type="button" className="btn btn-warning btn-lg fw-bold mt-2" onClick={() => {
                trackEvent("guest_challenge_start", {})
                setPlaying(true)
              }}>
              Accetta la sfida
            </button>
          </>
        ) : (
          <div className={`${styles.hint} text-start mt-3`}>
            <i className="bi bi-hourglass-bottom"></i>
            <div>
              Questa sfida non è più disponibile (è scaduta o qualcun altro l&apos;ha già giocata). Registrati gratis per sfidare{" "}
              {challenger} e tutti gli altri!
              <div className="mt-2">
                <Link to={`/register?ref=${encodeURIComponent(info.ref)}`} className="btn btn-primary btn-sm">
                  Registrati
                </Link>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
