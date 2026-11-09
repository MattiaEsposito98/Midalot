import { useCallback, useEffect, useRef, useState } from "react"
import { useNavigate, useParams } from "react-router-dom"
import api from "../../service/api"
import quizStyles from "../Quiz/Quiz.module.css"
import { logError } from "../../utils/logger"
import QuestionAudio from "../../components/QuestionAudio/QuestionAudio"
import { notifyChallengesChanged } from "../../hooks/useChallengeCount"
import QuestionCategory from "./QuestionCategory"
import styles from "./Sfide.module.css"
import UserAvatar from "../../components/UserAvatar/UserAvatar"
import UserTitle from "../../components/UserAvatar/UserTitle"

const POLL_MS = 1000
const TICK_MS = 200

function formatClock(ms) {
  const total = Math.max(0, Math.ceil(ms / 1000))
  const minutes = String(Math.floor(total / 60)).padStart(2, "0")
  const seconds = String(total % 60).padStart(2, "0")
  return `${minutes}:${seconds}`
}

// Sfida live: come la sala del Midalario, e' il server a decidere quale
// domanda e' attiva (polling); il tempo di risposta lo misura il server.
function SfidaLive() {
  const { id } = useParams()
  const navigate = useNavigate()

  const [state, setState] = useState(null)
  const [error, setError] = useState("")
  const [busy, setBusy] = useState(false)
  const [selected, setSelected] = useState({ questionId: null, answerId: null })
  const [, setTick] = useState(0)

  // Scarto tra orologio del server e del browser, aggiornato a ogni poll.
  const offsetRef = useRef(0)
  const countdownTargetRef = useRef(null)

  const fetchState = useCallback(async () => {
    try {
      const res = await api.get(`/challenges/${id}/live`)
      const data = res.data

      offsetRef.current = new Date(data.server_time).getTime() - Date.now()
      countdownTargetRef.current =
        data.phase === "countdown" ? Date.now() + data.starting_in_ms : null

      setState(data)
      setError("")
    } catch (err) {
      logError(err)
      if (err.response?.status === 404) setError("Sfida non trovata")
    }
  }, [id])

  useEffect(() => {
    fetchState()
    const poll = setInterval(fetchState, POLL_MS)
    const tick = setInterval(() => setTick((t) => t + 1), TICK_MS)

    return () => {
      clearInterval(poll)
      clearInterval(tick)
    }
  }, [fetchState])

  const phase = state?.phase

  useEffect(() => {
    if (phase === "finished" || phase === "closed" || phase === "converted") {
      notifyChallengesChanged()
      navigate(`/sfide/${id}`, { replace: true })
    }
  }, [phase, id, navigate])

  async function accept() {
    setBusy(true)

    try {
      await api.post(`/challenges/${id}/live/accept`)
      await fetchState()
    } catch (err) {
      logError(err)
      setError(err.response?.data?.message || "Impossibile accettare la sfida")
    } finally {
      setBusy(false)
    }
  }

  async function startNow() {
    setBusy(true)

    try {
      await api.post(`/challenges/${id}/live/start`)
      await fetchState()
    } catch (err) {
      logError(err)
      setError(err.response?.data?.message || "Impossibile avviare la sfida")
    } finally {
      setBusy(false)
    }
  }

  async function decline() {
    setBusy(true)

    try {
      await api.post(`/challenges/${id}/decline`)
      notifyChallengesChanged()
      navigate("/sfide", { replace: true })
    } catch (err) {
      logError(err)
      setError(err.response?.data?.message || "Operazione non riuscita")
      setBusy(false)
    }
  }

  async function answer(question, answerId) {
    if (selected.questionId === question.id) return

    setSelected({ questionId: question.id, answerId })

    try {
      await api.post(`/challenges/${id}/live/answer`, {
        question_id: question.id,
        answer_id: answerId,
      })
    } catch (err) {
      logError(err)
    }
  }

  if (error) {
    return (
      <div className={quizStyles.centerBox}>
        <div className={quizStyles.errorCard}>
          <h2 className={quizStyles.errorTitle}>Attenzione</h2>
          <p className={quizStyles.errorText}>{error}</p>
          <button className="btn btn-primary" onClick={() => navigate("/sfide")}>
            Torna alle sfide
          </button>
        </div>
      </div>
    )
  }

  if (!state) {
    return (
      <div className={quizStyles.centerBox}>
        <div className="spinner-border text-primary"></div>
      </div>
    )
  }

  const { challenger } = state.challenge
  const others = state.challenge.others
  const opponentsLabel = others.map((o) => o.nickname).join(", ")

  if (phase === "lobby") {
    const expiresIn = new Date(state.expires_at).getTime() - (Date.now() + offsetRef.current)
    const isChallenger = state.role === "challenger"
    const acceptedInvitees = state.players.filter((p) => !p.is_me && p.status === "accepted" && p.id !== challenger.id)

    return (
      <div className={quizStyles.centerBox}>
        <div className={`${styles.panel} text-center`}>
          <div className="mb-2" style={{ fontSize: "2.4rem" }}>
            <i className="bi bi-lightning-charge-fill text-warning"></i>
          </div>
          <h1 className={styles.title}>Sfida live</h1>

          {isChallenger ? (
            <p className={styles.subtitle}>
              Attendi che gli invitati accettino. Parte da sola quando tutti hanno risposto, oppure la avvii tu.
            </p>
          ) : (
            <p className={styles.subtitle}>
              <strong>{challenger.nickname}</strong> ti sfida in diretta su 7 domande.
            </p>
          )}

          <div className="text-start my-3">
            {state.players.map((p) => (
              <div key={p.id} className={`${styles.playerRow} ${p.is_me ? styles.playerRowMe : ""}`}>
                <span className="d-flex align-items-center gap-2">
                  <UserAvatar url={p.avatar_url} frame={p.avatar_frame} name={p.nickname} size={32} />
                  <span>
                    {p.is_me ? "Tu" : p.nickname} <UserTitle title={p.avatar_title} /> <span className={styles.meta}>Lv {p.level}</span>
                  </span>
                </span>
                <span
                  className={`${styles.statusTag} ${
                    p.status === "accepted" ? styles.statusOk : p.status === "declined" ? styles.statusOut : ""
                  }`}
                >
                  {p.id === challenger.id
                    ? "Ha lanciato la sfida"
                    : p.status === "accepted"
                      ? "Pronto"
                      : p.status === "declined"
                        ? "Ha rifiutato"
                        : "In attesa..."}
                </span>
              </div>
            ))}
          </div>

          {isChallenger ? (
            <>
              <button className="btn btn-warning btn-lg fw-bold w-100" disabled={busy || !state.can_start} onClick={startNow}>
                {state.can_start ? `Avvia ora con ${acceptedInvitees.length + 1} giocatori` : "Serve almeno un invitato pronto"}
              </button>
              <p className={`${styles.meta} mt-2`}>
                Chi non ha ancora accettato resta fuori. Se nessuno accetta entro {formatClock(expiresIn)} la sfida
                diventa differita (72 ore).
              </p>
              <button className="btn btn-outline-danger btn-sm mt-1" disabled={busy} onClick={decline}>
                Annulla sfida
              </button>
            </>
          ) : state.my_status === "accepted" ? (
            <>
              <div className="spinner-border text-primary my-2"></div>
              <p className={styles.meta}>
                Sei pronto. La sfida parte quando tutti hanno risposto o quando {challenger.nickname} la avvia.
              </p>
            </>
          ) : (
            <>
              <p className={styles.meta}>Quando accetti, aspetti gli altri invitati e poi parte il conto alla rovescia.</p>
              <div className="d-grid gap-2 mt-3">
                <button className="btn btn-warning btn-lg fw-bold" disabled={busy} onClick={accept}>
                  Accetta e inizia
                </button>
                <button className="btn btn-outline-danger btn-sm" disabled={busy} onClick={decline}>
                  Rifiuta
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    )
  }

  if (phase === "missed") {
    return (
      <div className={quizStyles.centerBox}>
        <div className={`${styles.panel} text-center`}>
          <h1 className={styles.title}>Sfida già iniziata</h1>
          <p className={styles.subtitle}>
            La sfida live è partita senza di te. Puoi vedere i risultati quando sarà conclusa.
          </p>
          <button className="btn btn-primary" onClick={() => navigate("/sfide")}>
            Torna alle sfide
          </button>
        </div>
      </div>
    )
  }

  if (phase === "countdown") {
    const remaining = Math.max(0, Math.ceil((countdownTargetRef.current - Date.now()) / 1000))

    return (
      <div className={quizStyles.centerBox}>
        <div className={`${styles.panel} text-center`}>
          <p className={styles.subtitle}>Sfida live</p>
          <div className={styles.versusRow}>
            {state.players.map((p, i) => (
              <span key={p.id} className="d-flex align-items-center gap-2">
                {i > 0 && <span className={styles.vs}>VS</span>}
                <span className={styles.versusPlayer}>
                  <UserAvatar url={p.avatar_url} frame={p.avatar_frame} name={p.nickname} size={state.players.length > 3 ? 56 : 72} />
                  <span>{p.is_me ? "Tu" : p.nickname}</span>
                  <UserTitle title={p.avatar_title} />
                </span>
              </span>
            ))}
          </div>
          <div className="display-1 fw-bold">{remaining || "Via!"}</div>
          <p className={styles.meta}>Preparati: le domande compaiono in contemporanea.</p>
        </div>
      </div>
    )
  }

  if (phase === "question") {
    const q = state.question
    const nowServer = Date.now() + offsetRef.current
    const startsAt = new Date(q.starts_at).getTime()
    const endsAt = new Date(q.ends_at).getTime()
    const totalMs = Math.max(1, endsAt - startsAt)
    const remainingMs = Math.max(0, endsAt - nowServer)
    const timePercent = (remainingMs / totalMs) * 100
    const progressPercent = ((q.index + 1) / state.total_questions) * 100

    const myAnswerId = selected.questionId === q.id ? selected.answerId : null
    const answered = state.has_answered || myAnswerId !== null

    return (
      <div className={quizStyles.page}>
        <div className="container">
          <div className={quizStyles.topBar}>
            <div>
              <h1 className={quizStyles.quizTitle}>Sfida live{opponentsLabel ? ` vs ${opponentsLabel}` : ""}</h1>
              <p className={quizStyles.quizSubtitle}>
                Domanda {q.index + 1} di {state.total_questions}
              </p>
            </div>

            <div className={quizStyles.timerCard}>
              <span className={quizStyles.timerLabel}>Tempo rimasto</span>
              <strong className={quizStyles.timerValue}>{Math.ceil(remainingMs / 1000)}s</strong>
            </div>
          </div>

          <div className={quizStyles.progressWrap}>
            <div className={quizStyles.progressLabelRow}>
              <span>Avanzamento</span>
              <span>{Math.round(progressPercent)}%</span>
            </div>
            <div className={quizStyles.progressBar}>
              <div className={quizStyles.progressFill} style={{ width: `${progressPercent}%` }} />
            </div>
          </div>

          <div className={quizStyles.questionCard} key={q.id}>
            <div className={quizStyles.timeBarWrap}>
              <div className={quizStyles.timeBar}>
                <div className={quizStyles.timeBarFill} style={{ width: `${timePercent}%` }} />
              </div>
            </div>

            <div className={quizStyles.questionHeader}>
              <h2 className={quizStyles.questionText}>{q.question_text}</h2>
            </div>

            <QuestionCategory category={q.category} subcategory={q.subcategory} training={q.training} />

            {q.image && (
              <div className={quizStyles.mediaWrap}>
                <img src={q.image} alt="Domanda" className={quizStyles.image} draggable="false" />
              </div>
            )}

            {q.audio && (
              <div className={quizStyles.mediaWrap}>
                <QuestionAudio
                  src={q.audio}
                  startSeconds={q.audio_start_seconds}
                  endSeconds={q.audio_end_seconds}
                  className={quizStyles.mediaControl}
                />
              </div>
            )}

            {q.video && (
              <div className={quizStyles.mediaWrap}>
                <video controls className={quizStyles.video}>
                  <source src={q.video} />
                </video>
              </div>
            )}

            <div className={quizStyles.answersGrid}>
              {q.answers.map((a, index) => (
                <button
                  key={a.id}
                  type="button"
                  className={`${quizStyles.answerBtn} ${myAnswerId === a.id ? quizStyles.answerSelected : ""}`}
                  onClick={() => answer(q, a.id)}
                  disabled={answered}
                >
                  <span className={quizStyles.answerIndex}>{String.fromCharCode(65 + index)}</span>
                  <span className={quizStyles.answerText}>{a.answer_text}</span>
                </button>
              ))}
            </div>

            <div className={`${styles.meta} text-center mt-3`}>
              {answered ? "Risposta inviata. " : ""}
              {state.others_total > 0 &&
                (state.others_answered >= state.others_total
                  ? "Tutti hanno risposto."
                  : `Hanno risposto ${state.others_answered} su ${state.others_total} avversari.`)}
            </div>
          </div>
        </div>
      </div>
    )
  }

  // finishing (e qualunque fase transitoria): si resta in attesa del risultato.
  return (
    <div className={quizStyles.centerBox}>
      <div className={`${styles.panel} text-center`}>
        <div className="spinner-border text-primary mb-3"></div>
        <p className={styles.subtitle}>Calcolo del risultato...</p>
      </div>
    </div>
  )
}

export default SfidaLive
