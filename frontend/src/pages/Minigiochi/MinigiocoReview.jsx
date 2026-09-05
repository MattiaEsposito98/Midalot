import { useEffect, useState } from "react"
import { Link, useParams } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "../QuizReview/QuizReview.module.css"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import { API_BASE } from "../../service/api"

function MinigiocoReview() {
  const { id } = useParams()
  const { token } = useAuth()

  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    async function loadReview() {
      try {
        setLoading(true)
        setError("")

        const res = await fetch(`${API_BASE}/api/minigiochi/${id}/review`, {
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
  }, [id, token])

  function formatTime(ms) {
    if (ms == null) return "-"
    return `${(ms / 1000).toFixed(1)}s`
  }

  function getOutcome(r) {
    if (r.is_correct) return { label: "Corretta", className: styles.outcomeCorrect, icon: "bi-check-circle-fill" }
    if (r.is_timeout) return { label: "Tempo scaduto", className: styles.outcomeTimeout, icon: "bi-clock-history" }
    return { label: "Sbagliata", className: styles.outcomeWrong, icon: "bi-x-circle-fill" }
  }

  function renderTastieraRotta(r) {
    return (
      <>
        <p className={styles.questionText}>Parola cifrata: {r.parola_cifrata}</p>
        <div className={styles.answersGrid}>
          <div className={styles.answerBox}>
            <span className={styles.answerLabel}>La tua risposta</span>
            <strong className={styles.answerValue}>
              {r.risposta_utente || (r.is_timeout ? "Nessuna risposta (timeout)" : "-")}
            </strong>
          </div>

          {!r.is_correct && (
            <div className={styles.answerBox}>
              <span className={styles.answerLabel}>Parola corretta</span>
              <strong className={`${styles.answerValue} ${styles.correctText}`}>
                {r.parola_corretta || "-"}
              </strong>
            </div>
          )}

          <div className={styles.answerBox}>
            <span className={styles.answerLabel}>Tentativi falliti</span>
            <strong className={styles.answerValue}>{r.tentativi_falliti}</strong>
          </div>

          <div className={styles.answerBox}>
            <span className={styles.answerLabel}>Tempo impiegato</span>
            <strong className={styles.answerValue}>{formatTime(r.time_taken)}</strong>
          </div>
        </div>
      </>
    )
  }

  function renderSaltoTemporale(r) {
    const correctOrder = r.items_corretti || []
    const itemDisplay = (item, index) => item.label || `Elemento ${index + 1}`
    const labelById = new Map(correctOrder.map((item, index) => [item.id, itemDisplay(item, index)]))
    const userLabels = (r.ordine_utente || []).map((itemId) => labelById.get(itemId) || "?")

    return (
      <>
        <p className={styles.questionText}>
          Ordina cronologicamente: {correctOrder.map(itemDisplay).join(" · ")}
        </p>

        <div className={styles.answersGrid}>
          <div className={styles.answerBox}>
            <span className={styles.answerLabel}>La tua sequenza</span>
            <strong className={styles.answerValue}>
              {userLabels.length > 0
                ? userLabels.join(" → ")
                : r.is_timeout
                  ? "Nessuna risposta (timeout)"
                  : "-"}
            </strong>
          </div>

          {!r.is_correct && (
            <div className={styles.answerBox}>
              <span className={styles.answerLabel}>Sequenza corretta</span>
              <strong className={`${styles.answerValue} ${styles.correctText}`}>
                {correctOrder.map(itemDisplay).join(" → ")}
              </strong>
            </div>
          )}

          <div className={styles.answerBox}>
            <span className={styles.answerLabel}>Tempo impiegato</span>
            <strong className={styles.answerValue}>{formatTime(r.time_taken)}</strong>
          </div>
        </div>
      </>
    )
  }

  function renderTrovaIntruso(r) {
    const items = r.items || []
    const itemDisplay = (item, index) => item.label || `Elemento ${index + 1}`
    const labelById = new Map(items.map((item, index) => [item.id, itemDisplay(item, index)]))
    const intrusoLabel = labelById.get(r.intruso_id) || "-"
    const sceltoLabel = r.scelto_id != null ? labelById.get(r.scelto_id) || "-" : null

    return (
      <>
        <p className={styles.questionText}>Elementi: {items.map(itemDisplay).join(" · ")}</p>

        <div className={styles.answersGrid}>
          <div className={styles.answerBox}>
            <span className={styles.answerLabel}>La tua scelta</span>
            <strong className={styles.answerValue}>
              {sceltoLabel || (r.is_timeout ? "Nessuna risposta (timeout)" : "-")}
            </strong>
          </div>

          {!r.is_correct && (
            <div className={styles.answerBox}>
              <span className={styles.answerLabel}>Intruso corretto</span>
              <strong className={`${styles.answerValue} ${styles.correctText}`}>{intrusoLabel}</strong>
            </div>
          )}

          <div className={styles.answerBox}>
            <span className={styles.answerLabel}>Tempo impiegato</span>
            <strong className={styles.answerValue}>{formatTime(r.time_taken)}</strong>
          </div>
        </div>
      </>
    )
  }

  function renderRoundBody(r, tipo) {
    if (tipo === "salto_temporale") return renderSaltoTemporale(r)
    if (tipo === "trova_intruso") return renderTrovaIntruso(r)
    return renderTastieraRotta(r)
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
        <Link to="/minigiochi" className="btn btn-primary">
          Torna ai Minigiochi
        </Link>
      </div>
    )
  }

  if (!data) return null

  const rounds = data.rounds || []
  const showLeaderboardLink = data.leaderboard_visible

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>Riepilogo minigioco</h1>
          <p className={styles.subtitle}>{data.minigioco.title}</p>
        </div>

        <div className={styles.headerRight}>
          <div className={styles.summaryBox}>
            <span className={styles.summaryLabel}>Punteggio finale</span>
            <strong className={styles.summaryValue}>{formatQuizScore(data.score)}</strong>
          </div>

          {showLeaderboardLink && (
            <Link to={`/minigiochi/${id}/leaderboard`} className="btn btn-warning">
              <i className="bi bi-trophy-fill"></i>
              Vedi classifica
            </Link>
          )}
        </div>
      </div>

      <div className={styles.list}>
        {rounds.map((r, index) => {
          const outcome = getOutcome(r)

          return (
            <div key={r.id} className={styles.card}>
              <div className={styles.cardHeader}>
                <span className={styles.questionIndex}>Domanda {index + 1}</span>
                <span className={`${styles.outcomeBadge} ${outcome.className}`}>
                  <i className={`bi ${outcome.icon}`}></i>
                  {outcome.label}
                </span>
              </div>

              {renderRoundBody(r, data.minigioco.tipo)}
            </div>
          )
        })}
      </div>

      <div className={styles.footerActions}>
        <Link to="/minigiochi" className={`btn btn-primary ${styles.backBtn}`}>
          <i className="bi bi-arrow-left"></i>
          Torna ai Minigiochi
        </Link>
      </div>
    </div>
  )
}

export default MinigiocoReview
