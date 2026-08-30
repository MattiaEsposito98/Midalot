import { useState } from "react"
import { Link, useParams } from "react-router-dom"
import shared from "./TastieraRotta.module.css"
import styles from "./TrovaIntruso.module.css"
import { formatQuizScore } from "../../utils/quizScore"
import { useMinigiocoAttempt } from "../../hooks/useMinigiocoAttempt"

function TrovaIntruso() {
  const { id } = useParams()

  const {
    minigioco,
    currentRound,
    currentIndex,
    loading,
    error,
    result,
    submitting,
    roundLocked,
    feedback,
    timeLeft,
    submitAnswer,
    handleBackToMinigiochi,
  } = useMinigiocoAttempt(id, { retryOnWrong: false })

  const [selectedId, setSelectedId] = useState(null)
  const [seenRoundId, setSeenRoundId] = useState(currentRound?.id)

  if (currentRound?.id !== seenRoundId) {
    setSeenRoundId(currentRound?.id)
    setSelectedId(null)
  }

  async function handleSelect(itemId) {
    if (submitting || roundLocked) return
    setSelectedId(itemId)
    await submitAnswer(itemId)
  }

  function formatSeconds(seconds) {
    return `${seconds}s`
  }

  if (loading) {
    return (
      <div className={shared.centerBox}>
        <div className="spinner-border text-primary"></div>
        <p className={shared.centerText}>Caricamento minigioco...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className={shared.centerBox}>
        <div className={shared.errorCard}>
          <h2 className={shared.errorTitle}>Attenzione</h2>
          <p className={shared.errorText}>{error}</p>
          <button className="btn btn-primary" onClick={handleBackToMinigiochi}>
            Torna ai Minigiochi
          </button>
        </div>
      </div>
    )
  }

  if (result) {
    return (
      <div className={shared.centerBox}>
        <div className={shared.resultCard}>
          <div className={shared.resultIcon}>
            <i className="bi bi-trophy-fill"></i>
          </div>
          <h1 className={shared.resultTitle}>Minigioco completato</h1>
          <p className={shared.resultSubtitle}>
            Hai terminato tutte le domande del minigioco.
          </p>
          <div className={shared.scoreBox}>
            <span className={shared.scoreLabel}>Punteggio finale</span>
            <strong className={shared.scoreValue}>{formatQuizScore(result.score)}</strong>
          </div>
          <button className="btn btn-primary" onClick={handleBackToMinigiochi}>
            Torna ai Minigiochi
          </button>
          <Link to={`/minigiochi/${id}/review`} className="btn btn-outline-primary">
            <i className="bi bi-clipboard-check"></i>
            Rivedi il minigioco
          </Link>
          {minigioco?.leaderboard_visible && (
            <Link to={`/minigiochi/${id}/leaderboard`} className="btn btn-warning">
              <i className="bi bi-trophy-fill"></i>
              Classifica
            </Link>
          )}
        </div>
      </div>
    )
  }

  if (!minigioco || !currentRound) return null

  const progressPercent = ((currentIndex + 1) / minigioco.total_rounds) * 100
  const timePercent =
    Number(currentRound.time_limit_seconds) > 0
      ? Math.max(0, (timeLeft / Number(currentRound.time_limit_seconds)) * 100)
      : 0
  const locked = submitting || roundLocked

  return (
    <div className={shared.page}>
      <div className="container">
        <div className={shared.topBar}>
          <div>
            <h1 className={shared.quizTitle}>{minigioco.title}</h1>
            <p className={shared.quizSubtitle}>
              Domanda {currentIndex + 1} di {minigioco.total_rounds}
            </p>
          </div>

          <div className={shared.timerCard}>
            <span className={shared.timerLabel}>Tempo rimasto</span>
            <strong className={shared.timerValue}>{formatSeconds(timeLeft)}</strong>
          </div>
        </div>

        <div className={shared.progressWrap}>
          <div className={shared.progressLabelRow}>
            <span>Avanzamento minigioco</span>
            <span>{Math.round(progressPercent)}%</span>
          </div>

          <div className={shared.progressBar}>
            <div className={shared.progressFill} style={{ width: `${progressPercent}%` }} />
          </div>
        </div>

        <div className={shared.questionCard}>
          <div className={shared.timeBarWrap}>
            <div className={shared.timeBar}>
              <div className={shared.timeBarFill} style={{ width: `${timePercent}%` }} />
            </div>
          </div>

          <div className={styles.instructions}>
            <i className="bi bi-search"></i>
            Trova l&apos;intruso tra questi 4 elementi
          </div>

          {feedback && (
            <div
              className={`${shared.feedback} ${
                feedback.type === "correct" ? shared.feedbackCorrect : shared.feedbackWrong
              }`}
            >
              {feedback.message}
            </div>
          )}

          <div className={styles.grid}>
            {currentRound.items.map((item) => (
              <button
                key={item.id}
                type="button"
                className={`${styles.tile} ${selectedId === item.id ? styles.tileSelected : ""}`}
                onClick={() => handleSelect(item.id)}
                disabled={locked}
              >
                {item.image && (
                  <div className={styles.tileImage}>
                    <img src={item.image} alt="" />
                  </div>
                )}
                <span className={styles.tileLabel}>{item.label}</span>
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}

export default TrovaIntruso
