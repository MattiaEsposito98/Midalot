import { useEffect, useState } from "react"
import { Link, useParams } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "./Training.module.css"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import { API_BASE } from "../../service/api"

function TrainingLeaderboard() {
  const { id } = useParams()
  const { token } = useAuth()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    if (!token) {
      setLoading(false)
      return
    }

    async function loadLeaderboard() {
      try {
        setLoading(true)
        setError("")

        const res = await fetch(`${API_BASE}/api/training/quizzes/${id}/leaderboard`, {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
        })

        const json = await res.json()

        if (!res.ok) {
          setError(json.message || "Errore nel caricamento della classifica")
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

    loadLeaderboard()
  }, [id, token])

  if (loading) {
    return (
      <div className={`container ${styles.loadingWrap}`}>
        <div className="spinner-border"></div>
        <p className={styles.loadingText}>Caricamento classifica...</p>
      </div>
    )
  }

  if (!token) {
    return (
      <div className={`container ${styles.page}`}>
        <div className="alert alert-info">
          Accedi per vedere la classifica di questo training.
        </div>
        <Link to="/login" className="btn btn-primary">Accedi</Link>
      </div>
    )
  }

  if (error) {
    return (
      <div className={`container ${styles.page}`}>
        <div className="alert alert-danger">{error}</div>
        <Link to="/training" className="btn btn-primary">Torna al training</Link>
      </div>
    )
  }

  if (!data) return null

  const results = data.results || []

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.hero}>
        <span className={styles.eyebrow}>
          <i className="bi bi-trophy-fill"></i>
          Classifica training
        </span>
        <h1 className={styles.title}>{data.quiz.title}</h1>
        <p className={styles.subtitle}>
          Classifica di questo singolo training, categoria "{data.quiz.category.name}".
        </p>
      </div>

      <section className={styles.section}>
        <h2 className={styles.sectionTitle}>Classifica di questo training</h2>

        {results.length === 0 && (
          <p className={styles.muted}>Ancora nessun risultato salvato.</p>
        )}

        {results.map((row) => (
          <div className={styles.leaderboardRow} key={`${row.position}-${row.nickname}-${row.score}`}>
            <span><strong>#{row.position}</strong> {row.nickname}</span>
            <span>{formatQuizScore(row.score)} punti · {row.correct_answers}/{row.total_questions}</span>
          </div>
        ))}
      </section>

      <div className="d-flex gap-2">
        <Link to={`/training/play/${id}`} className="btn btn-primary">
          Rigioca questo training
        </Link>
        <Link to={`/training/${data.quiz.category.slug}`} className="btn btn-outline-secondary">
          Torna alla categoria
        </Link>
      </div>
    </div>
  )
}

export default TrainingLeaderboard
