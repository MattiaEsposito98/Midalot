import { useEffect, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import XpGain from "../../components/XpGain/XpGain"
import { notifyChallengesChanged } from "../../hooks/useChallengeCount"
import styles from "./Sfide.module.css"

const CLOSED_LABEL = {
  expired: "Sfida scaduta: nessuno può più giocarla.",
  declined: "Sfida rifiutata.",
  cancelled: "Sfida annullata.",
}

function formatDeadline(iso) {
  return new Date(iso).toLocaleString("it-IT", {
    weekday: "short",
    day: "numeric",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  })
}

function Review({ review }) {
  return (
    <div className="mt-4">
      <h2 className={styles.sectionTitle}>Domande e risposte</h2>

      {review.questions.map((q, index) => (
        <div key={q.id} className={styles.reviewItem}>
          <div className={styles.reviewQuestion}>
            {index + 1}. {q.question_text}
          </div>

          {q.image && <img src={q.image} alt="Domanda" className="img-fluid mb-2" style={{ maxHeight: 180 }} />}

          {q.answers.map((a) => {
            const pickedByMe = q.mine?.answer_id === a.id
            const pickedByThem = q.theirs?.answer_id === a.id
            const cls = a.is_correct
              ? styles.reviewCorrect
              : pickedByMe || pickedByThem
                ? styles.reviewWrongPick
                : ""

            return (
              <div key={a.id} className={`${styles.reviewAnswer} ${cls}`}>
                <span>
                  {a.is_correct && <i className="bi bi-check-circle-fill text-success me-1"></i>}
                  {a.answer_text}
                </span>
                <span>
                  {pickedByMe && <span className={styles.tag}>Tu</span>}{" "}
                  {pickedByThem && <span className={styles.tag}>{review.other.nickname}</span>}
                </span>
              </div>
            )
          })}

          <div className={`${styles.meta} mt-1`}>
            {q.mine?.is_timeout && "Tu: tempo scaduto. "}
            {q.theirs?.is_timeout && `${review.other.nickname}: tempo scaduto.`}
          </div>
        </div>
      ))}
    </div>
  )
}

function SfidaDettaglio() {
  const { id } = useParams()
  const navigate = useNavigate()

  const [challenge, setChallenge] = useState(null)
  const [review, setReview] = useState(null)
  const [error, setError] = useState("")
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    let cancelled = false

    async function load() {
      try {
        const res = await api.get(`/challenges/${id}`)
        if (cancelled) return

        setChallenge(res.data)

        if (res.data.status !== "open") {
          const rev = await api.get(`/challenges/${id}/review`)
          if (!cancelled) setReview(rev.data)
        }
      } catch (err) {
        logError(err)
        if (!cancelled) setError(err.response?.data?.message || "Sfida non trovata")
      }
    }

    load()

    return () => {
      cancelled = true
    }
  }, [id])

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

  if (error) {
    return (
      <div className={`container ${styles.page}`}>
        <div className="alert alert-danger">{error}</div>
        <Link to="/sfide" className="btn btn-outline-secondary">
          Torna alle sfide
        </Link>
      </div>
    )
  }

  if (!challenge) {
    return (
      <div className={`container ${styles.page}`}>
        <div className="spinner-border text-primary"></div>
      </div>
    )
  }

  const { other, status } = challenge
  const closed = status !== "open"
  const canGiveUp = status === "open" && !challenge.opponent_state.started
  const meScore = challenge.me.score
  const otherScore = challenge.opponent_state.score

  return (
    <div className={`container ${styles.page}`}>
      <div className={`${styles.panel} ${styles.panelWide}`}>
        <div className={styles.meta}>{challenge.categories.join(", ")}</div>

        {status === "completed" && (
          <div className={styles.resultBanner}>
            {challenge.result === "won" && "Hai vinto! 🏆"}
            {challenge.result === "lost" && "Hai perso"}
            {challenge.result === "draw" && "Pareggio"}
          </div>
        )}

        {CLOSED_LABEL[status] && <div className={styles.resultBanner}>{CLOSED_LABEL[status]}</div>}

        <div className={styles.scoreRow}>
          <div className={styles.player}>
            Tu
            {closed && <span className={styles.playerScore}>{formatQuizScore(meScore)}</span>}
          </div>
          <div className={styles.vs}>VS</div>
          <div className={styles.player}>
            {other.nickname} <span className={styles.meta}>Lv {other.level}</span>
            {closed && <span className={styles.playerScore}>{formatQuizScore(otherScore)}</span>}
          </div>
        </div>

        {status === "completed" && <XpGain awarded={challenge.xp_awarded} xp={challenge.xp} />}

        {status === "open" && (
          <div className="text-center">
            {!challenge.me.completed ? (
              <>
                <p className={styles.meta}>
                  7 domande a tempo, più sei veloce più punti fai. Le risposte giuste si vedranno a sfida conclusa.
                  Scade {formatDeadline(challenge.expires_at)}.
                </p>
                <Link to={`/sfide/${id}/gioca`} className="btn btn-warning btn-lg fw-bold">
                  {challenge.my_started ? "Riprendi la sfida" : "Gioca ora"}
                </Link>
              </>
            ) : (
              <p className="fw-bold">
                Hai finito! In attesa che {other.nickname} giochi: risultati e risposte appariranno a sfida conclusa
                (entro {formatDeadline(challenge.expires_at)}).
              </p>
            )}

            {canGiveUp && (
              <div className="mt-3">
                <button className="btn btn-outline-danger btn-sm" disabled={busy} onClick={decline}>
                  {challenge.direction === "sent" ? "Annulla sfida" : "Rifiuta sfida"}
                </button>
              </div>
            )}
          </div>
        )}

        <div className="text-center mt-3">
          <Link to="/sfide" className="btn btn-outline-secondary btn-sm">
            Tutte le sfide
          </Link>
        </div>

        {review && review.questions.length > 0 && <Review review={review} />}
      </div>
    </div>
  )
}

export default SfidaDettaglio
