import { useState } from "react"
import { Link, useParams } from "react-router-dom"
import shared from "./TastieraRotta.module.css"
import styles from "./VeroFalso.module.css"
import { formatQuizScore } from "../../utils/quizScore"
import { useMinigiocoAttempt } from "../../hooks/useMinigiocoAttempt"

/**
 * Con una spiegazione da leggere il round resta visibile piu' a lungo prima
 * di passare avanti: tempo base + un margine proporzionale alla lunghezza
 * del testo, entro un minimo e un massimo ragionevoli.
 */
function advanceDelayFor(data) {
  if (!data?.spiegazione) return 500

  return Math.min(7000, Math.max(2200, 900 + data.spiegazione.length * 35))
}

function VeroFalso() {
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
  } = useMinigiocoAttempt(id, { retryOnWrong: false, getAdvanceDelayMs: advanceDelayFor })

  const [selected, setSelected] = useState(null)
  const [seenRoundId, setSeenRoundId] = useState(currentRound?.id)

  if (currentRound?.id !== seenRoundId) {
    setSeenRoundId(currentRound?.id)
    setSelected(null)
  }

  async function handleSelect(value) {
    if (submitting || roundLocked) return
    setSelected(value)
    await submitAnswer(value)
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
            <i className="bi bi-question-circle"></i>
            Questa affermazione è vera o falsa?
          </div>

          <p className={styles.affermazione}>{currentRound.affermazione}</p>

          {feedback && (
            <div
              className={`${shared.feedback} ${
                feedback.type === "correct" ? shared.feedbackCorrect : shared.feedbackWrong
              }`}
            >
              {feedback.message}
            </div>
          )}

          {feedback?.spiegazione && (
            <div className={styles.explanationBox}>
              <strong
                className={
                  feedback.type === "correct" ? styles.explanationHeaderCorrect : styles.explanationHeaderWrong
                }
              >
                {feedback.type === "correct"
                  ? "Esatto! Ecco perché"
                  : feedback.timeout
                    ? "Tempo scaduto: ecco perché"
                    : "Sbagliato: ecco perché"}
              </strong>
              {feedback.spiegazione}
            </div>
          )}

          <div className={styles.choices}>
            <button
              type="button"
              className={`${styles.choiceBtn} ${styles.choiceTrue} ${selected === true ? styles.choiceSelected : ""}`}
              onClick={() => handleSelect(true)}
              disabled={locked}
            >
              <i className="bi bi-check-circle-fill"></i>
              Vero
            </button>
            <button
              type="button"
              className={`${styles.choiceBtn} ${styles.choiceFalse} ${selected === false ? styles.choiceSelected : ""}`}
              onClick={() => handleSelect(false)}
              disabled={locked}
            >
              <i className="bi bi-x-circle-fill"></i>
              Falso
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

export default VeroFalso
