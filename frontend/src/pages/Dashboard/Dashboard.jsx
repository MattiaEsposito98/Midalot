import { useEffect, useState } from "react"
import { useAuth } from "../../context/AuthContext"
import { Link } from "react-router-dom"
import styles from "./Dashboard.module.css"

function Dashboard() {
  const { token } = useAuth()
  const [quizzes, setQuizzes] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function loadQuizzes() {
      try {
        const res = await fetch("http://localhost:8000/api/my-quizzes", {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const data = await res.json()
        setQuizzes(data.quizzes || [])
      } catch (error) {
        console.error("Errore caricamento quiz", error)
      } finally {
        setLoading(false)
      }
    }

    loadQuizzes()
  }, [token])

  function getStatusLabel(status) {
    if (status === "completed") return "Completato"
    if (status === "in_progress") return "In corso"
    if (status === "expired") return "Scaduto"
    return "Disponibile"
  }

  function getStatusClass(status) {
    if (status === "completed") return styles.statusCompleted
    if (status === "in_progress") return styles.statusInProgress
    if (status === "expired") return styles.statusExpired
    return styles.statusAvailable
  }

  function getFooterContent(q) {
    if (q.status === "completed") {
      return (
        <button className="btn btn-outline-secondary w-100" disabled>
          Quiz già completato
        </button>
      )
    }

    if (q.status === "expired") {
      return (
        <button className="btn btn-outline-danger w-100" disabled>
          Quiz scaduto e non completato
        </button>
      )
    }

    return (
      <Link to={`/quiz/${q.id}`} className={`btn btn-primary w-100 ${styles.startBtn}`}>
        {q.status === "in_progress" ? "Riprendi quiz" : "Inizia quiz"}
      </Link>
    )
  }

  if (loading) {
    return (
      <div className={`container ${styles.loadingWrap}`}>
        <div className="spinner-border text-primary"></div>
        <p className={styles.loadingText}>Caricamento quiz...</p>
      </div>
    )
  }

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>I tuoi quiz</h1>
          <p className={styles.subtitle}>
            Qui trovi tutti i quiz assegnati a te, sia attivi che passati.
          </p>
        </div>
      </div>

      {quizzes.length === 0 && (
        <div className={`alert alert-info ${styles.emptyBox}`}>
          Nessun quiz assegnato al momento.
        </div>
      )}

      <div className="row">
        {quizzes.map((q) => (
          <div className="col-md-6 col-xl-4 mb-4" key={q.id}>
            <div className={styles.card}>
              <div className={styles.cardTop}>
                <span className={`${styles.statusBadge} ${getStatusClass(q.status)}`}>
                  {getStatusLabel(q.status)}
                </span>
              </div>

              <h3 className={styles.cardTitle}>{q.title}</h3>

              <p className={styles.cardDescription}>
                {q.description || "Nessuna descrizione"}
              </p>

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
                  <span className={styles.infoLabel}>Punteggio</span>
                  <strong className={styles.infoValue}>
                    {q.completed ? q.score : "-"}
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

export default Dashboard