import { useEffect, useState } from "react"
import { useAuth } from "../../context/AuthContext"
import styles from "./Storico.module.css"

function Storico() {
  const { token } = useAuth()
  const [quizzes, setQuizzes] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function loadQuizzes() {
      try {
        const res = await fetch(`${import.meta.env.VITE_API_URL}/api/my-quizzes`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const data = await res.json()

        const allQuizzes = data.quizzes || []
        const historyQuizzes = allQuizzes.filter(
          (q) => q.status === "completed" || q.status === "expired"
        )

        setQuizzes(historyQuizzes)
      } catch (error) {
        console.error("Errore caricamento storico quiz", error)
      } finally {
        setLoading(false)
      }
    }

    loadQuizzes()
  }, [token])

  function getStatusLabel(status) {
    if (status === "completed") return "Completato"
    if (status === "expired") return "Scaduto"
    return "Storico"
  }

  function getStatusClass(status) {
    if (status === "completed") return styles.statusCompleted
    if (status === "expired") return styles.statusExpired
    return styles.statusAvailable
  }

  function getFooterContent(q) {
    if (q.status === "completed") {
      return (
        <div className="d-flex flex-column gap-2 w-100">
          <button className="btn btn-outline-secondary w-100" disabled>
            Quiz completato
          </button>

          {q.leaderboard_visible && (
            <button
              className="btn btn-primary w-100"
              onClick={() => {
                window.location.href = `/quiz/${q.id}/leaderboard`
              }}
            >
              🏆 Vedi classifica
            </button>
          )}
        </div>
      )
    }

    return (
      <div className="d-flex flex-column gap-2 w-100">
        <button className="btn btn-outline-danger w-100" disabled>
          Quiz scaduto
        </button>

        {q.leaderboard_visible && (
          <button
            className="btn btn-primary w-100"
            onClick={() => {
              window.location.href = `/quiz/${q.id}/leaderboard`
            }}
          >
            🏆 Vedi classifica
          </button>
        )}
      </div>
    )
  }

  if (loading) {
    return (
      <div className={`container ${styles.loadingWrap}`}>
        <div className="spinner-border text-primary"></div>
        <p className={styles.loadingText}>Caricamento storico...</p>
      </div>
    )
  }

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>Storico quiz</h1>
          <p className={styles.subtitle}>
            Qui trovi i quiz completati o scaduti.
          </p>
        </div>
      </div>

      {quizzes.length === 0 && (
        <div className={`alert alert-info ${styles.emptyBox}`}>
          Nessun quiz nello storico.
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
                    {q.status === "completed" ? q.score ?? "-" : "-"}
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

export default Storico