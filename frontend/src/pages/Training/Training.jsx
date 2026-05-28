import { useEffect, useMemo, useState } from "react"
import { Link, useParams } from "react-router-dom"
import { useAuth } from "../../context/AuthContext"
import styles from "./Training.module.css"

const API_URL = import.meta.env.VITE_API_URL

function Training() {
  const { categorySlug } = useParams()
  const { token } = useAuth()
  const [categories, setCategories] = useState([])
  const [categoryData, setCategoryData] = useState(null)
  const [progress, setProgress] = useState(null)
  const [leaderboard, setLeaderboard] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    async function loadBase() {
      try {
        setLoading(true)
        setError("")

        const res = await fetch(`${API_URL}/api/training/categories`, {
          headers: { Accept: "application/json" },
        })
        const data = await res.json()

        if (!res.ok) {
          setError(data.message || "Errore nel caricamento del training")
          return
        }

        setCategories(data.categories || [])

        if (token) {
          const progressRes = await fetch(`${API_URL}/api/training/progress`, {
            headers: {
              Accept: "application/json",
              Authorization: `Bearer ${token}`,
            },
          })
          const progressData = await progressRes.json()
          if (progressRes.ok) setProgress(progressData)
        }
      } catch (err) {
        console.error(err)
        setError("Errore di connessione")
      } finally {
        setLoading(false)
      }
    }

    loadBase()
  }, [token])

  useEffect(() => {
    async function loadCategory() {
      if (!categorySlug) {
        setCategoryData(null)
        setLeaderboard(null)
        return
      }

      try {
        setError("")

        const res = await fetch(`${API_URL}/api/training/categories/${categorySlug}/quizzes`, {
          headers: { Accept: "application/json" },
        })
        const data = await res.json()

        if (!res.ok) {
          setError(data.message || "Categoria training non disponibile")
          return
        }

        setCategoryData(data)

        if (token) {
          const leaderboardRes = await fetch(`${API_URL}/api/training/categories/${categorySlug}/leaderboard`, {
            headers: {
              Accept: "application/json",
              Authorization: `Bearer ${token}`,
            },
          })
          const leaderboardData = await leaderboardRes.json()
          if (leaderboardRes.ok) setLeaderboard(leaderboardData)
        }
      } catch (err) {
        console.error(err)
        setError("Errore di connessione")
      }
    }

    loadCategory()
  }, [categorySlug, token])

  const progressBySlug = useMemo(() => {
    const map = {}
    ;(progress?.categories || []).forEach((item) => {
      map[item.category.slug] = item
    })
    return map
  }, [progress])

  if (loading) {
    return (
      <div className={`container ${styles.page}`}>
        <div className="spinner-border text-primary"></div>
      </div>
    )
  }

  if (error) {
    return (
      <div className={`container ${styles.page}`}>
        <div className="alert alert-danger">{error}</div>
      </div>
    )
  }

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.hero}>
        <h1 className={styles.title}>Training</h1>
        <p className={styles.subtitle}>
          Allenati per categoria con quiz casuali. Gli ospiti possono provare liberamente; gli utenti registrati salvano progressi e classifiche.
        </p>
      </div>

      {!token && (
        <div className="alert alert-info">
          Puoi giocare anche senza account. Registrati per salvare le tue sessioni di training e vedere i tuoi progressi.
        </div>
      )}

      {token && progress && (
        <div className={styles.stats}>
          <div className={styles.statCard}>
            <span>Categorie allenate</span>
            <strong>{progress.categories?.length || 0}</strong>
          </div>
          <div className={styles.statCard}>
            <span>Sessioni salvate</span>
            <strong>{progress.recent_attempts?.length || 0}</strong>
          </div>
          <div className={styles.statCard}>
            <span>Miglior punteggio</span>
            <strong>{Math.max(0, ...(progress.categories || []).map((item) => item.best_score || 0))}</strong>
          </div>
        </div>
      )}

      {!categorySlug && (
        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>Scegli una categoria</h2>
          <div className={styles.grid}>
            {categories.map((category) => {
              const stats = progressBySlug[category.slug]
              return (
                <div className={styles.card} key={category.id}>
                  <div>
                    <h3 className={styles.cardTitle}>{category.name}</h3>
                    <p className={styles.muted}>{category.description || "Training disponibili per questa categoria."}</p>
                  </div>
                  <div className="d-grid gap-2">
                    {stats && (
                      <small className={styles.muted}>
                        Miglior punteggio: <strong>{stats.best_score}</strong>
                      </small>
                    )}
                    <Link to={`/training/${category.slug}`} className="btn btn-primary">
                      Apri categoria
                    </Link>
                  </div>
                </div>
              )
            })}
          </div>
        </section>
      )}

      {categorySlug && categoryData && (
        <>
          <section className={styles.section}>
            <div className="d-flex justify-content-between align-items-center gap-3 mb-3">
              <div>
                <h2 className={styles.sectionTitle}>{categoryData.category.name}</h2>
                <p className={`${styles.muted} mb-0`}>{categoryData.category.description || "Scegli un training e inizia."}</p>
              </div>
              <Link to="/training" className="btn btn-outline-secondary">
                Categorie
              </Link>
            </div>

            <div className={styles.quizGrid}>
              {categoryData.quizzes.map((quiz) => (
                <div className={styles.card} key={quiz.id}>
                  <div>
                    <h3 className={styles.cardTitle}>{quiz.title}</h3>
                    <p className={styles.muted}>{quiz.description || "Quiz di allenamento"}</p>
                    <small className={styles.muted}>
                      Domande: {quiz.question_mode === "all" ? "tutte" : quiz.question_mode}
                    </small>
                  </div>
                  <Link to={`/training/play/${quiz.id}`} className="btn btn-primary mt-3">
                    Inizia training
                  </Link>
                </div>
              ))}
            </div>

            {categoryData.quizzes.length === 0 && (
              <div className="alert alert-info mb-0">Nessun training disponibile in questa categoria.</div>
            )}
          </section>

          {token && leaderboard && (
            <section className={styles.section}>
              <h2 className={styles.sectionTitle}>Classifica categoria</h2>
              {(leaderboard.results || []).length === 0 && (
                <p className={styles.muted}>Ancora nessun risultato salvato.</p>
              )}
              {(leaderboard.results || []).slice(0, 10).map((row) => (
                <div className={styles.leaderboardRow} key={`${row.position}-${row.nickname}-${row.score}`}>
                  <span>
                    <strong>#{row.position}</strong> {row.nickname}
                  </span>
                  <span>
                    {row.score} punti · {row.correct_answers}/{row.total_questions}
                  </span>
                </div>
              ))}
            </section>
          )}

          {!token && (
            <div className="alert alert-warning">
              Le classifiche e i progressi sono visibili solo agli utenti registrati.
            </div>
          )}
        </>
      )}

      {token && progress?.recent_attempts?.length > 0 && (
        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>Ultime sessioni</h2>
          {progress.recent_attempts.map((attempt) => (
            <div className={styles.historyRow} key={attempt.id}>
              <span>
                <strong>{attempt.category_name}</strong> · {attempt.quiz_title}
              </span>
              <span>
                {attempt.score} punti · {attempt.correct_answers}/{attempt.total_questions}
              </span>
            </div>
          ))}
        </section>
      )}
    </div>
  )
}

export default Training
