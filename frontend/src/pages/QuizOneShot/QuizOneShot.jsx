import { useEffect, useMemo, useState } from "react"
import { Link } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "./QuizOneShot.module.css"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import { API_BASE } from "../../service/api"
import WeeklyLeaderboardBox from "../../components/WeeklyLeaderboardBox/WeeklyLeaderboardBox"

function QuizOneShot() {
  const { token } = useAuth()
  const [quizzes, setQuizzes] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    async function loadQuizzes() {
      try {
        setLoading(true)
        setError("")

        const res = await fetch(`${API_BASE}/api/my-quizzes`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const data = await res.json()

        if (!res.ok) {
          setError(data.message || "Errore nel caricamento dei quiz")
          return
        }

        const allQuizzes = data.quizzes || []
        const activeQuizzes = allQuizzes.filter((q) => q.is_active)

        setQuizzes(activeQuizzes)
      } catch (err) {
        logError("Errore caricamento quiz", err)
        setError("Errore di connessione durante il caricamento dei quiz")
      } finally {
        setLoading(false)
      }
    }

    loadQuizzes()
  }, [token])

  const sortedQuizzes = useMemo(() => {
    const priority = {
      in_progress: 0,
      available: 1,
      completed: 2,
    }

    return [...quizzes].sort((a, b) => {
      const aPriority = priority[a.status] ?? 99
      const bPriority = priority[b.status] ?? 99

      if (aPriority !== bPriority) {
        return aPriority - bPriority
      }

      return (a.title || "").localeCompare(b.title || "")
    })
  }, [quizzes])

  function getStatusLabel(status) {
    if (status === "completed") return "Completato"
    if (status === "in_progress") return "In corso"
    return "Disponibile"
  }

  function getStatusClass(status) {
    if (status === "completed") return styles.statusCompleted
    if (status === "in_progress") return styles.statusInProgress
    return styles.statusAvailable
  }

  function getCardClass(status) {
    if (status === "completed") return styles.cardCompleted
    if (status === "in_progress") return styles.cardInProgress
    return ""
  }

  function getStatusText(status) {
    if (status === "completed") return "Completato"
    if (status === "in_progress") return "Da completare"
    return "Pronto"
  }

  function getFooterContent(q) {
    if (q.status === "completed") {
      return (
        <div className="d-flex flex-column gap-2 w-100">
          <button className="btn btn-outline-success w-100" disabled>
            <i className="bi bi-check-circle-fill"></i>
            Quiz completato
          </button>

          <Link
            to={`/quiz/${q.id}/review`}
            className="btn btn-outline-primary w-100"
          >
            <i className="bi bi-clipboard-check"></i>
            Rivedi quiz
          </Link>

          {q.leaderboard_visible && (
            <Link
              to={`/quiz/${q.id}/leaderboard`}
              className={`btn btn-warning w-100 ${styles.leaderboardBtn}`}
            >
              <i className="bi bi-trophy-fill"></i>
              Vedi classifica
            </Link>
          )}
        </div>
      )
    }

    return (
      <div className="d-flex flex-column gap-2 w-100">
        <Link
          to={`/quiz/${q.id}`}
          className={`btn btn-primary w-100 ${styles.startBtn}`}
        >
          <i className="bi bi-play-fill"></i>
          {q.status === "in_progress" ? "Riprendi quiz" : "Inizia quiz"}
        </Link>

        {q.leaderboard_visible && (
          <Link
            to={`/quiz/${q.id}/leaderboard`}
            className="btn btn-outline-warning w-100"
          >
            <i className="bi bi-trophy-fill"></i>
            Classifica
          </Link>
        )}
      </div>
    )
  }

  if (loading) {
    return (
      <div className={`container ${styles.loadingWrap}`}>
        <div className="spinner-border"></div>
        <p className={styles.loadingText}>Caricamento quiz...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className={`container-wide ${styles.page}`}>
        <div className={`alert alert-danger ${styles.emptyBox}`}>
          {error}
        </div>
      </div>
    )
  }

  return (
    <div className={`container-wide ${styles.page}`}>
      <div className={styles.header}>
        <div>
          <span className={styles.eyebrow}>
            <i className="bi bi-grid-1x2-fill"></i>
            Quiz One Shot
          </span>
          <h1 className={styles.title}>Quiz One Shot</h1>
          <p className={styles.subtitle}>
            Qui trovi i quiz attivi: disponibili, in corso o gia' completati.
          </p>
        </div>

        <div className={styles.leaderboardSection}>
          <WeeklyLeaderboardBox />
        </div>
      </div>

      {sortedQuizzes.length === 0 && (
        <div className={`alert alert-info ${styles.emptyBox}`}>
          Nessun quiz attivo al momento.
        </div>
      )}

      <div className="row">
        {sortedQuizzes.map((q) => (
          <div className="col-md-6 col-xl-4 mb-4" key={q.id}>
            <div className={`${styles.card} ${getCardClass(q.status)}`}>
              <div className={styles.cardHeader}>
                {q.image && (
                  <div className={styles.cardImageWrap}>
                    <img src={q.image} alt="" className={styles.cardImage} />
                  </div>
                )}

                <div className={styles.cardHeaderText}>
                  <div className={styles.cardTop}>
                    <span className={`${styles.statusBadge} ${getStatusClass(q.status)}`}>
                      {getStatusLabel(q.status)}
                    </span>

                    {q.leaderboard_visible && (
                      <span className={styles.leaderboardBadge}>
                        <i className="bi bi-trophy-fill"></i>
                        Classifica
                      </span>
                    )}
                  </div>

                  <h3 className={styles.cardTitle}>{q.title}</h3>

                  <p className={styles.cardDescription}>
                    {q.description || "Nessuna descrizione"}
                  </p>
                </div>
              </div>

              <div className={styles.infoGrid}>
                <div className={styles.infoItem}>
                  <span className={styles.infoLabel}>Domande</span>
                  <strong className={styles.infoValue}>{q.questions_count}</strong>
                </div>

                <div className={styles.infoItem}>
                  <span className={styles.infoLabel}>Tempo medio</span>
                  <strong className={styles.infoValue}>
                    {q.avg_time ? `${Math.round(q.avg_time)}s` : "-"}
                  </strong>
                </div>

                <div className={styles.infoItem}>
                  <span className={styles.infoLabel}>Durata stimata</span>
                  <strong className={styles.infoValue}>
                    {q.total_time ? `${Math.ceil(q.total_time / 60)} min` : "-"}
                  </strong>
                </div>

                <div className={styles.infoItem}>
                  <span className={styles.infoLabel}>Stato</span>
                  <strong className={styles.infoValue}>
                    {getStatusText(q.status)}
                  </strong>
                </div>

                <div className={styles.infoItem}>
                  <span className={styles.infoLabel}>Punteggio</span>
                  <strong className={styles.infoValue}>
                    {q.status === "completed" ? formatQuizScore(q.score) : "-"}
                  </strong>
                </div>

                <div className={styles.infoItem}>
                  <span className={styles.infoLabel}>Classifica</span>
                  <strong className={styles.infoValue}>
                    {q.leaderboard_visible ? "Disponibile" : "Nascosta"}
                  </strong>
                </div>
              </div>

              <div className={styles.footer}>
                {getFooterContent(q)}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export default QuizOneShot
