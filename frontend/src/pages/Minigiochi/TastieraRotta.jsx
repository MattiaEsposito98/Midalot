import { useEffect, useRef, useState } from "react"
import { Link, useParams } from "react-router-dom"
import styles from "./TastieraRotta.module.css"
import { formatQuizScore } from "../../utils/quizScore"
import { useMinigiocoAttempt } from "../../hooks/useMinigiocoAttempt"

const KEYBOARD_ROWS = ["QWERTYUIOP", "ASDFGHJKL", "ZXCVBNM"]

function TastieraRotta() {
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
  } = useMinigiocoAttempt(id, { retryOnWrong: true })

  const [inputValue, setInputValue] = useState("")
  const [tentativiFalliti, setTentativiFalliti] = useState(0)
  const [seenRoundId, setSeenRoundId] = useState(currentRound?.id)
  const inputRef = useRef(null)

  if (currentRound?.id !== seenRoundId) {
    setSeenRoundId(currentRound?.id)
    setInputValue("")
    setTentativiFalliti(0)
  }

  useEffect(() => {
    if (!roundLocked && !submitting) {
      inputRef.current?.focus()
    }
  }, [roundLocked, submitting, currentIndex])

  async function handleSubmit(e) {
    e.preventDefault()

    if (submitting || roundLocked || !inputValue.trim()) return

    const data = await submitAnswer(inputValue.trim())

    if (data) {
      setTentativiFalliti(data.tentativi_falliti || 0)
      if (!data.correct) {
        setInputValue("")
      }
    }
  }

  function handleVirtualKeyPress(letter) {
    if (submitting || roundLocked) return
    setInputValue((prev) => prev + letter)
    inputRef.current?.focus()
  }

  function handleVirtualBackspace() {
    if (submitting || roundLocked) return
    setInputValue((prev) => prev.slice(0, -1))
    inputRef.current?.focus()
  }

  function formatSeconds(seconds) {
    return `${seconds}s`
  }

  if (loading) {
    return (
      <div className={styles.centerBox}>
        <div className="spinner-border text-primary"></div>
        <p className={styles.centerText}>Caricamento minigioco...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className={styles.centerBox}>
        <div className={styles.errorCard}>
          <h2 className={styles.errorTitle}>Attenzione</h2>
          <p className={styles.errorText}>{error}</p>
          <button className="btn btn-primary" onClick={handleBackToMinigiochi}>
            Torna ai Minigiochi
          </button>
        </div>
      </div>
    )
  }

  if (result) {
    return (
      <div className={styles.centerBox}>
        <div className={styles.resultCard}>
          <div className={styles.resultIcon}>
            <i className="bi bi-trophy-fill"></i>
          </div>
          <h1 className={styles.resultTitle}>Minigioco completato</h1>
          <p className={styles.resultSubtitle}>
            Hai terminato tutte le domande del minigioco.
          </p>
          <div className={styles.scoreBox}>
            <span className={styles.scoreLabel}>Punteggio finale</span>
            <strong className={styles.scoreValue}>{formatQuizScore(result.score)}</strong>
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

  return (
    <div className={styles.page}>
      <div className="container">
        <div className={styles.topBar}>
          <div>
            <h1 className={styles.quizTitle}>{minigioco.title}</h1>
            <p className={styles.quizSubtitle}>
              Domanda {currentIndex + 1} di {minigioco.total_rounds}
            </p>
          </div>

          <div className={styles.timerCard}>
            <span className={styles.timerLabel}>Tempo rimasto</span>
            <strong className={styles.timerValue}>{formatSeconds(timeLeft)}</strong>
          </div>
        </div>

        <div className={styles.progressWrap}>
          <div className={styles.progressLabelRow}>
            <span>Avanzamento minigioco</span>
            <span>{Math.round(progressPercent)}%</span>
          </div>

          <div className={styles.progressBar}>
            <div
              className={styles.progressFill}
              style={{ width: `${progressPercent}%` }}
            />
          </div>
        </div>

        <div className={styles.questionCard}>
          <div className={styles.timeBarWrap}>
            <div className={styles.timeBar}>
              <div
                className={styles.timeBarFill}
                style={{ width: `${timePercent}%` }}
              />
            </div>
          </div>

          <div className={styles.cipherWrap}>
            <span className={styles.cipherLabel}>Decifra la parola</span>
            <span className={styles.cipherWord}>{currentRound.parola_cifrata}</span>
          </div>

          {feedback && (
            <div
              className={`${styles.feedback} ${
                feedback.type === "correct" ? styles.feedbackCorrect : styles.feedbackWrong
              }`}
            >
              {feedback.message}
            </div>
          )}

          <form className={styles.answerForm} onSubmit={handleSubmit}>
            <input
              ref={inputRef}
              type="text"
              className={styles.answerInput}
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value.toUpperCase())}
              disabled={submitting || roundLocked}
              autoComplete="off"
              autoFocus
            />
            <button
              type="submit"
              className={`btn btn-primary ${styles.submitBtn}`}
              disabled={submitting || roundLocked || !inputValue.trim()}
            >
              Conferma
            </button>
          </form>

          {tentativiFalliti > 0 && (
            <p className={styles.attemptsInfo}>
              Tentativi falliti su questa domanda: {tentativiFalliti}
            </p>
          )}

          <div className={styles.keyboardWrap}>
            {KEYBOARD_ROWS.map((row) => (
              <div key={row} className={styles.keyboardRow}>
                {row.split("").map((letter) => (
                  <button
                    key={letter}
                    type="button"
                    className={styles.keyBtn}
                    onClick={() => handleVirtualKeyPress(letter)}
                    disabled={submitting || roundLocked}
                  >
                    {letter}
                  </button>
                ))}
              </div>
            ))}
            <div className={styles.keyboardRow}>
              <button
                type="button"
                className={`${styles.keyBtn} ${styles.keyBtnWide}`}
                onClick={handleVirtualBackspace}
                disabled={submitting || roundLocked}
              >
                <i className="bi bi-backspace-fill"></i>
                Cancella
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default TastieraRotta
