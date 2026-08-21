import { useEffect, useState } from "react"
import { Link, useParams } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "./QuizReview.module.css"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import { API_BASE } from "../../service/api"

function QuizReview({ kind = "quiz" }) {
  const { id } = useParams()
  const { token } = useAuth()
  const backTo = kind === "midalario" ? "/midalario" : "/dashboard"
  const backLabel = kind === "midalario" ? "Torna a Il Midalario" : "Torna ai Quiz One Shot"
  const apiPath = kind === "midalario" ? `midalario/quizzes/${id}/review` : `quizzes/${id}/review`

  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    async function loadReview() {
      try {
        setLoading(true)
        setError("")

        const res = await fetch(`${API_BASE}/api/${apiPath}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const json = await res.json()

        if (!res.ok) {
          setError(json.message || "Errore nel caricamento del riepilogo")
          return
        }

        setData(json)
      } catch (err) {
        logError(err)
        setError("Errore di connessione")
      } finally {
        setLoading(false)
      }
    }

    loadReview()
  }, [id, token, apiPath])

  function formatTime(ms) {
    if (ms == null) return "-"
    return `${(ms / 1000).toFixed(1)}s`
  }

  function getOutcome(q) {
    if (q.is_correct) return { label: "Corretta", className: styles.outcomeCorrect, icon: "bi-check-circle-fill" }
    if (q.is_timeout) return { label: "Tempo scaduto", className: styles.outcomeTimeout, icon: "bi-clock-history" }
    return { label: "Sbagliata", className: styles.outcomeWrong, icon: "bi-x-circle-fill" }
  }

  if (loading) {
    return (
      <div className={`container ${styles.loadingWrap}`}>
        <div className="spinner-border text-primary"></div>
        <p className={styles.loadingText}>Caricamento riepilogo...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className={`container ${styles.page}`}>
        <div className={`alert alert-danger ${styles.errorBox}`}>
          {error}
        </div>
        <Link to={backTo} className="btn btn-primary">
          {backLabel}
        </Link>
      </div>
    )
  }

  if (!data) return null

  const questions = data.questions || []

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>Riepilogo quiz</h1>
          <p className={styles.subtitle}>{data.quiz.title}</p>
        </div>

        <div className={styles.summaryBox}>
          <span className={styles.summaryLabel}>Punteggio finale</span>
          <strong className={styles.summaryValue}>{formatQuizScore(data.score)}</strong>
        </div>
      </div>

      <div className={styles.list}>
        {questions.map((q, index) => {
          const outcome = getOutcome(q)

          return (
            <div key={q.id} className={styles.card}>
              <div className={styles.cardHeader}>
                <span className={styles.questionIndex}>Domanda {index + 1}</span>
                <span className={`${styles.outcomeBadge} ${outcome.className}`}>
                  <i className={`bi ${outcome.icon}`}></i>
                  {outcome.label}
                </span>
              </div>

              <p className={styles.questionText}>{q.question_text}</p>

              {q.image && (
                <div className={styles.mediaWrap}>
                  <img src={q.image} alt="Domanda" className={styles.image} />
                </div>
              )}

              <div className={styles.answersGrid}>
                <div className={styles.answerBox}>
                  <span className={styles.answerLabel}>La tua risposta</span>
                  <strong className={styles.answerValue}>
                    {q.given_answer_text || (q.is_timeout ? "Nessuna risposta (timeout)" : "-")}
                  </strong>
                </div>

                {!q.is_correct && (
                  <div className={styles.answerBox}>
                    <span className={styles.answerLabel}>Risposta corretta</span>
                    <strong className={`${styles.answerValue} ${styles.correctText}`}>
                      {q.correct_answer_text || "-"}
                    </strong>
                  </div>
                )}

                <div className={styles.answerBox}>
                  <span className={styles.answerLabel}>Tempo impiegato</span>
                  <strong className={styles.answerValue}>{formatTime(q.time_taken)}</strong>
                </div>
              </div>
            </div>
          )
        })}
      </div>

      <Link to={backTo} className={`btn btn-primary ${styles.backBtn}`}>
        <i className="bi bi-arrow-left"></i>
        {backLabel}
      </Link>
    </div>
  )
}

export default QuizReview
