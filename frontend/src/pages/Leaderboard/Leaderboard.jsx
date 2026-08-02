import { useEffect, useState } from "react"
import { useParams } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "./Leaderboard.module.css"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"

function Leaderboard() {
  const { id } = useParams()
  const { token, user } = useAuth()

  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    async function loadLeaderboard() {
      try {
        setLoading(true)
        setError("")

        const res = await fetch(`/api/quizzes/${id}/leaderboard`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const json = await res.json()

        if (!res.ok) {
          setError(json.message || "Errore caricamento classifica")
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

  function formatTime(ms) {
    if (ms == null) return "-"
    const totalSeconds = Math.floor(ms / 1000)
    const minutes = Math.floor(totalSeconds / 60)
    const seconds = totalSeconds % 60
    const milliseconds = ms % 1000

    return `${minutes}:${seconds.toString().padStart(2, "0")}.${milliseconds
      .toString()
      .padStart(3, "0")}`
  }

  function getRankBadge(position) {
    if (position === 1) return "1"
    if (position === 2) return "2"
    if (position === 3) return "3"
    return `#${position}`
  }

  if (loading) {
    return (
      <div className={`container ${styles.loadingWrap}`}>
        <div className="spinner-border text-primary"></div>
        <p className={styles.loadingText}>Caricamento classifica...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className={`container ${styles.page}`}>
        <div className={`alert alert-danger ${styles.errorBox}`}>
          {error}
        </div>
      </div>
    )
  }

  if (!data) {
    return (
      <div className={`container ${styles.page}`}>
        <div className={`alert alert-warning ${styles.errorBox}`}>
          Nessun dato disponibile
        </div>
      </div>
    )
  }

  const results = data.results || []

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>Classifica</h1>
          <p className={styles.subtitle}>{data.quiz.title}</p>
        </div>

        <div className={styles.summaryBox}>
          <span className={styles.summaryLabel}>Partecipanti</span>
          <strong className={styles.summaryValue}>{results.length}</strong>
        </div>
      </div>

      {results.length === 0 && (
        <div className={`alert alert-info ${styles.emptyBox}`}>
          Nessun partecipante in classifica.
        </div>
      )}

      <div className={styles.list}>
        {results.map((r, index) => {
          const position = index + 1
          const isMe = user?.nickname === r.user.nickname
          const isTop3 = position <= 3

          return (
            <div
              key={`${r.user.nickname}-${position}`}
              className={`${styles.row} ${isMe ? styles.meRow : ""} ${isTop3 ? styles.topRow : ""}`}
            >
              <div className={styles.rankCol}>
                <div className={`${styles.rankBadge} ${isTop3 ? styles.topBadge : ""}`}>
                  {getRankBadge(position)}
                </div>
              </div>

              <div className={styles.mainCol}>
                <div className={styles.topLine}>
                  <div className={styles.nameWrap}>
                    <span className={styles.name}>{r.user.nickname}</span>
                    {isMe && <span className={styles.meTag}>Tu</span>}
                  </div>

                  <div className={styles.statusWrap}>
                    <span
                      className={`${styles.statusBadge} ${r.completed ? styles.completedBadge : styles.progressBadge
                        }`}
                    >
                      {r.completed ? "Completato" : "In corso"}
                    </span>
                  </div>
                </div>

                <div className={styles.statsGrid}>
                  <div className={styles.statBox}>
                    <span className={styles.statLabel}>Punteggio</span>
                    <strong className={styles.statValue}>{formatQuizScore(r.score)}</strong>
                  </div>

                  <div className={styles.statBox}>
                    <span className={styles.statLabel}>Corrette</span>
                    <strong className={styles.statValue}>
                      {r.correct_answers}/{r.total_questions}
                    </strong>
                  </div>

                  <div className={styles.statBox}>
                    <span className={styles.statLabel}>Tempo</span>
                    <strong className={styles.statValue}>{formatTime(r.total_time)}</strong>
                  </div>
                </div>
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}

export default Leaderboard
