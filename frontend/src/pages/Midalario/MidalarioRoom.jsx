import { useEffect, useMemo, useRef, useState } from "react"
import { Link, useParams } from "react-router-dom"
import styles from "./MidalarioRoom.module.css"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import QuestionAudio from "../../components/QuestionAudio/QuestionAudio"

const POLL_INTERVAL_MS = 2000
const TICK_INTERVAL_MS = 250

function shuffleArray(array) {
  const copy = [...array]

  for (let i = copy.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [copy[i], copy[j]] = [copy[j], copy[i]]
  }

  return copy
}

function formatSeconds(seconds) {
  return `${Math.max(0, seconds)}s`
}

function MidalarioRoom() {
  const { id } = useParams()

  const [status, setStatus] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [joining, setJoining] = useState(false)
  const [selectedAnswerId, setSelectedAnswerId] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [answeredQuestionId, setAnsweredQuestionId] = useState(null)
  const [, forceTick] = useState(0)

  const serverOffsetRef = useRef(0)

  async function fetchStatus() {
    try {
      const res = await api.get(`/midalario/quizzes/${id}/status`)
      serverOffsetRef.current = new Date(res.data.server_time).getTime() - Date.now()
      setStatus(res.data)
      setError("")
    } catch (err) {
      logError(err)
      setError("Errore di connessione")
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    fetchStatus()
    const interval = setInterval(fetchStatus, POLL_INTERVAL_MS)
    return () => clearInterval(interval)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id])

  useEffect(() => {
    const interval = setInterval(() => forceTick((t) => t + 1), TICK_INTERVAL_MS)
    return () => clearInterval(interval)
  }, [])

  useEffect(() => {
    setSelectedAnswerId(null)
  }, [status?.question?.id])

  const shuffledAnswers = useMemo(() => {
    if (!status?.question?.answers) return []
    return shuffleArray(status.question.answers)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [status?.question?.id])

  function correctedNow() {
    return Date.now() + serverOffsetRef.current
  }

  function getRemainingSeconds() {
    if (!status?.question?.ends_at) return 0
    const endsAt = new Date(status.question.ends_at).getTime()
    return Math.max(0, Math.ceil((endsAt - correctedNow()) / 1000))
  }

  function getRemainingPercent() {
    if (!status?.question) return 0
    const startsAt = new Date(status.question.starts_at).getTime()
    const endsAt = new Date(status.question.ends_at).getTime()
    const total = endsAt - startsAt
    if (total <= 0) return 0
    const remaining = Math.max(0, endsAt - correctedNow())
    return (remaining / total) * 100
  }

  async function handleJoin() {
    setJoining(true)
    setError("")
    try {
      await api.post(`/midalario/quizzes/${id}/join`)
      await fetchStatus()
    } catch (err) {
      logError(err)
      setError(err.response?.data?.message || "Errore durante l'iscrizione")
    } finally {
      setJoining(false)
    }
  }

  async function handleAnswer(answerId) {
    const question = status?.question

    if (!question || submitting || answeredQuestionId === question.id) return

    setSubmitting(true)
    setSelectedAnswerId(answerId)

    try {
      await api.post(`/midalario/quizzes/${id}/answer`, {
        question_id: question.id,
        answer_id: answerId,
      })
      setAnsweredQuestionId(question.id)
    } catch (err) {
      logError(err)
      setError(err.response?.data?.message || "Errore nell'invio della risposta")
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) {
    return (
      <div className={styles.centerBox}>
        <div className="spinner-border text-primary"></div>
        <p className={styles.centerText}>Caricamento sala...</p>
      </div>
    )
  }

  if (!status) {
    return (
      <div className={styles.centerBox}>
        <div className={styles.card}>
          <h1 className={styles.cardTitle}>Attenzione</h1>
          <p className={styles.cardText}>{error || "Impossibile caricare questo quiz."}</p>
          <Link to="/midalario" className="btn btn-primary">Torna a Il Midalario</Link>
        </div>
      </div>
    )
  }

  const { quiz, participants_count: participantsCount, total_questions: totalQuestions } = status
  const roomStatus = status.status

  if (status.completed) {
    return (
      <div className={styles.centerBox}>
        <div className={styles.resultCard}>
          <div className={styles.resultIcon}>
            <i className="bi bi-trophy-fill"></i>
          </div>
          <h1 className={styles.cardTitle}>Quiz completato!</h1>
          <p className={styles.cardText}>Hai terminato "{quiz.title}".</p>
          <div className={styles.scoreBox}>
            <span className={styles.scoreLabel}>Punteggio finale</span>
            <strong className={styles.scoreValue}>{formatQuizScore(status.score)}</strong>
          </div>
          <div className={styles.actionsRow}>
            <Link to={`/midalario/${id}/review`} className="btn btn-outline-primary">
              <i className="bi bi-clipboard-check"></i>
              Rivedi il quiz
            </Link>
            <Link to={`/midalario/${id}/leaderboard`} className="btn btn-warning">
              <i className="bi bi-trophy-fill"></i>
              Classifica
            </Link>
            <Link to="/midalario" className="btn btn-outline-secondary">
              Torna a Il Midalario
            </Link>
          </div>
        </div>
      </div>
    )
  }

  if (!status.joined) {
    return (
      <div className={styles.centerBox}>
        <div className={styles.card}>
          <h1 className={styles.cardTitle}>{quiz.title}</h1>
          <p className={styles.cardText}>{quiz.description}</p>

          {error && <div className="alert alert-danger">{error}</div>}

          {roomStatus === "open" && (
            <>
              <p className={styles.participantsLine}>{participantsCount} partecipanti iscritti finora.</p>
              <button className="btn btn-primary btn-lg" onClick={handleJoin} disabled={joining}>
                {joining ? "Iscrizione..." : "Partecipa"}
              </button>
            </>
          )}

          {roomStatus === "closed" && (
            <div className="alert alert-warning mb-0">
              Le iscrizioni sono chiuse: non puoi più partecipare a questa sessione.
            </div>
          )}

          {roomStatus === "running" && (
            <div className="alert alert-info mb-0">
              Il quiz è già in corso: non hai partecipato a questa sessione.
            </div>
          )}

          {roomStatus === "finished" && (
            <div className="alert alert-secondary mb-0">
              Questo quiz è terminato.
            </div>
          )}

          <Link to="/midalario" className={`btn btn-outline-secondary ${styles.backLink}`}>
            Torna a Il Midalario
          </Link>
        </div>
      </div>
    )
  }

  if (roomStatus === "open" || roomStatus === "closed") {
    return (
      <div className={styles.centerBox}>
        <div className={styles.card}>
          <div className="spinner-border text-primary mb-3"></div>
          <h1 className={styles.cardTitle}>{quiz.title}</h1>
          <p className={styles.cardText}>Sei in sala d'attesa.</p>
          <p className={styles.participantsLine}>{participantsCount} partecipanti iscritti.</p>
          <p className={styles.cardText}>
            {roomStatus === "open"
              ? "Aspetta che l'amministratore chiuda le iscrizioni e avvii il quiz."
              : "Le iscrizioni sono chiuse. Il quiz sta per iniziare, resta su questa pagina."}
          </p>
        </div>
      </div>
    )
  }

  if (roomStatus === "finished") {
    return (
      <div className={styles.centerBox}>
        <div className={styles.card}>
          <h1 className={styles.cardTitle}>{quiz.title}</h1>
          <p className={styles.cardText}>Il quiz è terminato.</p>
          <Link to="/midalario" className="btn btn-primary">Torna a Il Midalario</Link>
        </div>
      </div>
    )
  }

  const question = status.question

  if (!question) {
    return (
      <div className={styles.centerBox}>
        <div className="spinner-border text-primary"></div>
        <p className={styles.centerText}>Attendi il caricamento della prossima domanda...</p>
      </div>
    )
  }

  const hasAnswered = status.has_answered || answeredQuestionId === question.id
  const remainingSeconds = getRemainingSeconds()
  const remainingPercent = getRemainingPercent()
  const progressPercent = ((question.index + 1) / totalQuestions) * 100

  return (
    <div className={styles.page}>
      <div className="container">
        <div className={styles.topBar}>
          <div>
            <h1 className={styles.quizTitle}>{quiz.title}</h1>
            <p className={styles.quizSubtitle}>
              Domanda {question.index + 1} di {totalQuestions}
            </p>
          </div>

          <div className={styles.timerCard}>
            <span className={styles.timerLabel}>Tempo rimasto</span>
            <strong className={styles.timerValue}>{formatSeconds(remainingSeconds)}</strong>
          </div>
        </div>

        <div className={styles.progressWrap}>
          <div className={styles.progressLabelRow}>
            <span>Avanzamento quiz</span>
            <span>{Math.round(progressPercent)}%</span>
          </div>
          <div className={styles.progressBar}>
            <div className={styles.progressFill} style={{ width: `${progressPercent}%` }} />
          </div>
        </div>

        <div className={styles.questionCard}>
          <div className={styles.timeBarWrap}>
            <div className={styles.timeBar}>
              <div className={styles.timeBarFill} style={{ width: `${remainingPercent}%` }} />
            </div>
          </div>

          <div className={styles.questionHeader}>
            <h2 className={styles.questionText}>{question.question_text}</h2>
          </div>

          {question.image && (
            <div className={styles.mediaWrap}>
              <img src={question.image} alt="Domanda" className={styles.image} draggable="false" />
            </div>
          )}

          {question.audio && (
            <div className={styles.mediaWrap}>
              <QuestionAudio
                src={question.audio}
                startSeconds={question.audio_start_seconds}
                endSeconds={question.audio_end_seconds}
                className={styles.mediaControl}
              />
            </div>
          )}

          {question.video && (
            <div className={styles.mediaWrap}>
              <video controls className={styles.video}>
                <source src={question.video} />
              </video>
            </div>
          )}

          {hasAnswered ? (
            <div className={styles.waitingBox}>
              <i className="bi bi-check-circle-fill"></i>
              <p>
                Hai risposto! Attendi <strong>{formatSeconds(remainingSeconds)}</strong> per la prossima domanda...
              </p>
            </div>
          ) : (
            <div className={styles.answersGrid}>
              {shuffledAnswers.map((answer, index) => (
                <button
                  key={answer.id}
                  type="button"
                  className={`${styles.answerBtn} ${selectedAnswerId === answer.id ? styles.answerSelected : ""}`}
                  onClick={() => handleAnswer(answer.id)}
                  disabled={submitting || !!selectedAnswerId}
                >
                  <span className={styles.answerIndex}>{String.fromCharCode(65 + index)}</span>
                  <span className={styles.answerText}>{answer.answer_text}</span>
                </button>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default MidalarioRoom
