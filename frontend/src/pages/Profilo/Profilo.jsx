import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { useAuth } from "../../context/AuthContext"
import styles from "./Profilo.module.css"

function Profilo() {
  const { token, user } = useAuth()
  const [stats, setStats] = useState({
    total: 0,
    active: 0,
    completed: 0,
    expired: 0,
  })
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function loadStats() {
      try {
        const res = await fetch(`${import.meta.env.VITE_API_URL}/api/my-quizzes`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const data = await res.json()
        const quizzes = data.quizzes || []

        const total = quizzes.length
        const active = quizzes.filter(
          (q) => q.status === "available" || q.status === "in_progress"
        ).length
        const completed = quizzes.filter((q) => q.status === "completed").length
        const expired = quizzes.filter((q) => q.status === "expired").length

        setStats({
          total,
          active,
          completed,
          expired,
        })
      } catch (error) {
        console.error("Errore caricamento statistiche profilo", error)
      } finally {
        setLoading(false)
      }
    }

    loadStats()
  }, [token])

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <h1 className={styles.title}>Il mio profilo</h1>
        <p className={styles.subtitle}>
          Qui puoi consultare i tuoi dati e un riepilogo della tua attività.
        </p>
      </div>

      <div className="row g-4">
        <div className="col-12 col-lg-6">
          <div className={styles.card}>
            <h2 className={styles.cardTitle}>Informazioni personali</h2>

            <div className={styles.infoList}>
              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Nome</span>
                <strong className={styles.infoValue}>
                  {user?.name || user?.nome || "-"}
                </strong>
              </div>

              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Nickname</span>
                <strong className={styles.infoValue}>
                  {user?.nickname || "-"}
                </strong>
              </div>

              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Email</span>
                <strong className={styles.infoValue}>
                  {user?.email || "-"}
                </strong>
              </div>

              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Ruolo</span>
                <strong className={styles.infoValue}>
                  {user?.is_admin ? "Admin" : "Utente"}
                </strong>
              </div>
            </div>
          </div>
        </div>

        <div className="col-12 col-lg-6">
          <div className={styles.card}>
            <h2 className={styles.cardTitle}>Statistiche quiz</h2>

            {loading ? (
              <div className={styles.loadingBox}>
                <div className="spinner-border text-primary" role="status"></div>
                <p className={styles.loadingText}>Caricamento statistiche...</p>
              </div>
            ) : (
              <div className={styles.statsGrid}>
                <div className={styles.statItem}>
                  <span className={styles.statLabel}>Totale assegnati</span>
                  <strong className={styles.statValue}>{stats.total}</strong>
                </div>

                <div className={styles.statItem}>
                  <span className={styles.statLabel}>Quiz attivi</span>
                  <strong className={styles.statValue}>{stats.active}</strong>
                </div>

                <div className={styles.statItem}>
                  <span className={styles.statLabel}>Completati</span>
                  <strong className={styles.statValue}>{stats.completed}</strong>
                </div>

                <div className={styles.statItem}>
                  <span className={styles.statLabel}>Scaduti</span>
                  <strong className={styles.statValue}>{stats.expired}</strong>
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="col-12">
          <div className={styles.card}>
            <div className={styles.securityWrap}>
              <div>
                <h2 className={styles.cardTitle}>Sicurezza</h2>
                <p className={styles.securityText}>
                  Aggiorna la tua password per mantenere il tuo account al sicuro.
                </p>
              </div>

              <Link to="/cambia-password" className={`btn btn-primary ${styles.passwordBtn}`}>
                Cambia password
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Profilo