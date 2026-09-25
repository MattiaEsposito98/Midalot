import { useEffect, useMemo, useState } from "react"
import { Link } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "./MinigiochiList.module.css"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import { API_BASE } from "../../service/api"
import ComingSoon from "../../components/ComingSoon/ComingSoon"

const TIPO_LABELS = {
  tastiera_rotta: "Tastiera Rotta",
  salto_temporale: "Salto Temporale",
  trova_intruso: "Trova l'Intruso",
  vero_falso: "Vero o Falso",
}

function MinigiochiList() {
  const { token } = useAuth()
  const [minigiochi, setMinigiochi] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    async function loadMinigiochi() {
      try {
        setLoading(true)
        setError("")

        const res = await fetch(`${API_BASE}/api/my-minigiochi`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const data = await res.json()

        if (!res.ok) {
          setError(data.message || "Errore nel caricamento dei minigiochi")
          return
        }

        setMinigiochi(data.minigiochi || [])
      } catch (err) {
        logError("Errore caricamento minigiochi", err)
        setError("Errore di connessione durante il caricamento dei minigiochi")
      } finally {
        setLoading(false)
      }
    }

    loadMinigiochi()
  }, [token])

  const sortedMinigiochi = useMemo(() => {
    const priority = {
      in_progress: 0,
      available: 1,
      completed: 2,
      expired: 3,
    }

    return [...minigiochi].sort((a, b) => {
      const aPriority = priority[a.status] ?? 99
      const bPriority = priority[b.status] ?? 99

      if (aPriority !== bPriority) {
        return aPriority - bPriority
      }

      return (a.title || "").localeCompare(b.title || "")
    })
  }, [minigiochi])

  const groupedByTipo = useMemo(() => {
    const groups = {}

    sortedMinigiochi.forEach((m) => {
      const key = m.tipo || "altro"
      if (!groups[key]) groups[key] = []
      groups[key].push(m)
    })

    return Object.keys(groups)
      .sort((a, b) => (TIPO_LABELS[a] || a).localeCompare(TIPO_LABELS[b] || b))
      .map((tipo) => ({ tipo, label: TIPO_LABELS[tipo] || tipo, items: groups[tipo] }))
  }, [sortedMinigiochi])

  function getStatusLabel(status) {
    if (status === "completed") return "Completato"
    if (status === "in_progress") return "In corso"
    if (status === "expired") return "Non disponibile"
    return "Disponibile"
  }

  function getStatusClass(status) {
    if (status === "completed") return styles.statusCompleted
    if (status === "in_progress") return styles.statusInProgress
    if (status === "expired") return styles.statusExpired
    return styles.statusAvailable
  }

  function getCardClass(status) {
    if (status === "completed") return styles.cardCompleted
    if (status === "in_progress") return styles.cardInProgress
    if (status === "expired") return styles.cardExpired
    return ""
  }

  function getStatusText(status) {
    if (status === "completed") return "Completato"
    if (status === "in_progress") return "Da completare"
    if (status === "expired") return "Non disponibile"
    return "Pronto"
  }

  function getFooterContent(m) {
    if (m.status === "completed") {
      return (
        <div className="d-flex flex-column gap-2 w-100">
          <button className="btn btn-outline-success w-100" disabled>
            <i className="bi bi-check-circle-fill"></i>
            Minigioco completato
          </button>

          <Link
            to={`/minigiochi/${m.id}/review`}
            className="btn btn-outline-primary w-100"
          >
            <i className="bi bi-clipboard-check"></i>
            Rivedi minigioco
          </Link>

          {m.leaderboard_visible && (
            <Link
              to={`/minigiochi/${m.id}/leaderboard`}
              className={`btn btn-warning w-100 ${styles.leaderboardBtn}`}
            >
              <i className="bi bi-trophy-fill"></i>
              Vedi classifica
            </Link>
          )}
        </div>
      )
    }

    if (m.status === "expired") {
      return (
        <div className="d-flex flex-column gap-2 w-100">
          <button className="btn btn-outline-secondary w-100" disabled>
            <i className="bi bi-slash-circle"></i>
            Non più disponibile
          </button>

          {m.leaderboard_visible && (
            <Link
              to={`/minigiochi/${m.id}/leaderboard`}
              className="btn btn-outline-warning w-100"
            >
              <i className="bi bi-trophy-fill"></i>
              Classifica
            </Link>
          )}
        </div>
      )
    }

    return (
      <div className="d-flex flex-column gap-2 w-100">
        <Link
          to={`/minigiochi/${m.id}`}
          className={`btn btn-primary w-100 ${styles.startBtn}`}
        >
          <i className="bi bi-play-fill"></i>
          {m.status === "in_progress" ? "Riprendi minigioco" : "Inizia minigioco"}
        </Link>

        {m.leaderboard_visible && (
          <Link
            to={`/minigiochi/${m.id}/leaderboard`}
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
        <p className={styles.loadingText}>Caricamento minigiochi...</p>
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
        <span className={styles.eyebrow}>
          <i className="bi bi-joystick"></i>
          Minigiochi
        </span>
        <h1 className={styles.title}>Minigiochi</h1>
        <p className={styles.subtitle}>
          Metti alla prova la tua velocità con i minigiochi disponibili.
        </p>
      </div>

      {sortedMinigiochi.length === 0 && (
        <ComingSoon
          icon="bi-joystick"
          message="Nessun minigioco attivo in questo momento: stiamo preparando nuove sfide veloci. Torna a trovarci presto!"
        />
      )}

      {groupedByTipo.map((group) => (
        <section className={styles.typeSection} key={group.tipo}>
          <h2 className={styles.typeTitle}>{group.label}</h2>
          <div className="row">
            {group.items.map((m) => (
              <div className="col-md-6 col-xl-4 mb-4" key={m.id}>
                <div className={`${styles.card} ${getCardClass(m.status)}`}>
                  <div className={styles.cardHeader}>
                    {m.image && (
                      <div className={styles.cardImageWrap}>
                        <img src={m.image} alt="" className={styles.cardImage} />
                      </div>
                    )}

                    <div className={styles.cardHeaderText}>
                      <div className={styles.cardTop}>
                        <span className={`${styles.statusBadge} ${getStatusClass(m.status)}`}>
                          {getStatusLabel(m.status)}
                        </span>

                        {m.leaderboard_visible && (
                          <span className={styles.leaderboardBadge}>
                            <i className="bi bi-trophy-fill"></i>
                            Classifica
                          </span>
                        )}
                      </div>

                      <h3 className={styles.cardTitle}>{m.title}</h3>

                      <p className={styles.cardDescription}>
                        {m.description || "Nessuna descrizione"}
                      </p>
                    </div>
                  </div>

                  <div className={styles.infoGrid}>
                    <div className={styles.infoItem}>
                      <span className={styles.infoLabel}>Domande</span>
                      <strong className={styles.infoValue}>{m.rounds_count}</strong>
                    </div>

                    <div className={styles.infoItem}>
                      <span className={styles.infoLabel}>Durata stimata</span>
                      <strong className={styles.infoValue}>
                        {m.total_time ? `${Math.ceil(m.total_time / 60)} min` : "-"}
                      </strong>
                    </div>

                    <div className={styles.infoItem}>
                      <span className={styles.infoLabel}>Stato</span>
                      <strong className={styles.infoValue}>
                        {getStatusText(m.status)}
                      </strong>
                    </div>

                    <div className={styles.infoItem}>
                      <span className={styles.infoLabel}>Punteggio</span>
                      <strong className={styles.infoValue}>
                        {m.status === "completed" ? formatQuizScore(m.score) : "-"}
                      </strong>
                    </div>
                  </div>

                  <div className={styles.footer}>
                    {getFooterContent(m)}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>
      ))}
    </div>
  )
}

export default MinigiochiList
