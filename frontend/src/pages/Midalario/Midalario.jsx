import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import styles from "./Midalario.module.css"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import ComingSoon from "../../components/ComingSoon/ComingSoon"

const STATUS_LABELS = {
  open: ["Iscrizioni aperte", "statusOpen"],
  closed: ["Iscrizioni chiuse", "statusClosed"],
  running: ["In corso", "statusRunning"],
  finished: ["Terminato", "statusFinished"],
}

function Midalario() {
  const [quizzes, setQuizzes] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    api.get("/midalario/quizzes")
      .then((res) => setQuizzes(res.data.quizzes || []))
      .catch((err) => {
        logError(err)
        setError("Errore nel caricamento dei quiz Il Midalario")
      })
      .finally(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className={`container ${styles.loadingWrap}`}>
        <div className="spinner-border"></div>
        <p className={styles.loadingText}>Caricamento Midalario...</p>
      </div>
    )
  }

  return (
    <div className={`container-wide ${styles.page}`}>
      <div className={styles.header}>
        <span className={styles.eyebrow}>
          <i className="bi bi-broadcast"></i>
          Il Midalario
        </span>
        <h1 className={styles.title}>Il Midalario</h1>
        <p className={styles.subtitle}>
          Quiz live in contemporanea: partecipa, aspetta in sala che l'admin avvii il quiz e gioca insieme
          agli altri iscritti nello stesso momento.
        </p>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {quizzes.length === 0 && !error && (
        <ComingSoon
          icon="bi-broadcast"
          message="Nessun Midalario in programma al momento. Segui gli annunci sul sito per non perderti il prossimo evento dal vivo!"
        />
      )}

      <div className={styles.grid}>
        {quizzes.map((quiz) => {
          const [label, statusClass] = STATUS_LABELS[quiz.status] || ["-", "statusClosed"]

          return (
            <div className={styles.card} key={quiz.id}>
              <div className={styles.cardHeader}>
                {quiz.image && (
                  <div className={styles.cardImageWrap}>
                    <img src={quiz.image} alt="" className={styles.cardImage} />
                  </div>
                )}

                <div className={styles.cardHeaderText}>
                  <div className={styles.cardTop}>
                    <span className={`${styles.statusBadge} ${styles[statusClass]}`}>{label}</span>
                    {quiz.joined && <span className={styles.joinedBadge}>Sei iscritto</span>}
                  </div>

                  <h2 className={styles.cardTitle}>{quiz.title}</h2>
                  <p className={styles.cardDescription}>{quiz.description || "Nessuna descrizione"}</p>
                </div>
              </div>

              <div className={styles.infoGrid}>
                <div className={styles.infoItem}>
                  <span className={styles.infoLabel}>Domande</span>
                  <strong className={styles.infoValue}>{quiz.questions_count}</strong>
                </div>
                <div className={styles.infoItem}>
                  <span className={styles.infoLabel}>Partecipanti</span>
                  <strong className={styles.infoValue}>{quiz.participants_count}</strong>
                </div>
                <div className={styles.infoItem}>
                  <span className={styles.infoLabel}>Punteggio</span>
                  <strong className={styles.infoValue}>
                    {quiz.completed ? formatQuizScore(quiz.score) : "-"}
                  </strong>
                </div>
              </div>

              <div className={`${styles.footer} d-flex flex-column gap-2`}>
                {quiz.completed ? (
                  <>
                    <Link to={`/midalario/${quiz.id}/review`} className="btn btn-outline-success w-100">
                      <i className="bi bi-clipboard-check"></i>
                      Vedi riepilogo
                    </Link>
                    <Link to={`/midalario/${quiz.id}/leaderboard`} className="btn btn-warning w-100">
                      <i className="bi bi-trophy-fill"></i>
                      Classifica
                    </Link>
                  </>
                ) : (
                  <Link to={`/midalario/${quiz.id}`} className="btn btn-primary w-100">
                    <i className="bi bi-broadcast"></i>
                    {quiz.status === "open" ? "Partecipa" : "Vai alla sala"}
                  </Link>
                )}
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}

export default Midalario
