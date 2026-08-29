import { useEffect, useMemo, useRef, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "./TastieraRotta.module.css"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import { API_BASE } from "../../service/api"

const KEYBOARD_ROWS = ["QWERTYUIOP", "ASDFGHJKL", "ZXCVBNM"]

function TastieraRotta() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { token } = useAuth()

  const [minigioco, setMinigioco] = useState(null)
  const [attemptId, setAttemptId] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [currentIndex, setCurrentIndex] = useState(0)
  const [timeLeft, setTimeLeft] = useState(0)
  const [roundStartedAt, setRoundStartedAt] = useState(null)
  const [inputValue, setInputValue] = useState("")
  const [submitting, setSubmitting] = useState(false)
  const [result, setResult] = useState(null)
  const [roundLocked, setRoundLocked] = useState(false)
  const [feedback, setFeedback] = useState(null)
  const [tentativiFalliti, setTentativiFalliti] = useState(0)

  const timerRef = useRef(null)
  const timeoutTriggeredRef = useRef(false)
  const finishingRef = useRef(false)
  const visibilityAlertShownRef = useRef(false)
  const currentRoundRef = useRef(null)
  const currentIndexRef = useRef(0)
  const roundDeadlineRef = useRef(null)
  const submittingRef = useRef(false)
  const roundLockedRef = useRef(false)
  const minigiocoRef = useRef(null)
  const initedKeyRef = useRef(null)
  const inputRef = useRef(null)

  const currentRound = useMemo(() => {
    return minigioco?.rounds?.[currentIndex] || null
  }, [minigioco, currentIndex])

  function setSubmittingSafe(value) {
    submittingRef.current = value
    setSubmitting(value)
  }

  function setRoundLockedSafe(value) {
    roundLockedRef.current = value
    setRoundLocked(value)
  }

  useEffect(() => {
    currentRoundRef.current = currentRound
    currentIndexRef.current = currentIndex
  }, [currentRound, currentIndex])

  useEffect(() => {
    minigiocoRef.current = minigioco
  }, [minigioco])

  useEffect(() => {
    const initKey = `${id}:${token}`

    if (initedKeyRef.current === initKey) return
    initedKeyRef.current = initKey

    async function initMinigioco() {
      try {
        setLoading(true)
        setError("")
        setMinigioco(null)
        setAttemptId(null)
        setCurrentIndex(0)
        setResult(null)

        const res = await fetch(`${API_BASE}/api/minigiochi/${id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const data = await res.json()

        if (!res.ok) {
          setError(data.message || "Errore nel caricamento del minigioco")
          return
        }

        setMinigioco(data.minigioco)

        const startRes = await fetch(`${API_BASE}/api/minigiochi/${id}/start`, {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const startData = await startRes.json()

        if (!startRes.ok) {
          setError(startData.message || "Impossibile avviare il minigioco")
          return
        }

        setAttemptId(startData.attempt_id)
      } catch (err) {
        logError(err)
        setError("Errore di connessione")
      } finally {
        setLoading(false)
      }
    }

    initMinigioco()

    return () => {
      clearInterval(timerRef.current)
    }
  }, [id, token])

  useEffect(() => {
    if (!currentRound || !attemptId || result) return

    clearInterval(timerRef.current)

    const maxTime = Number(currentRound.time_limit_seconds || 0)
    const startedAt = Date.now()
    const deadline = startedAt + maxTime * 1000

    setTimeLeft(maxTime)
    setRoundStartedAt(startedAt)
    setInputValue("")
    setFeedback(null)
    setTentativiFalliti(0)
    setRoundLockedSafe(false)
    setSubmittingSafe(false)

    timeoutTriggeredRef.current = false
    roundDeadlineRef.current = deadline

    timerRef.current = setInterval(() => {
      const now = Date.now()
      const remainingMs = Math.max(0, roundDeadlineRef.current - now)
      const remainingSeconds = Math.ceil(remainingMs / 1000)

      setTimeLeft(remainingSeconds)

      if (remainingMs <= 0 && !timeoutTriggeredRef.current) {
        timeoutTriggeredRef.current = true
        clearInterval(timerRef.current)
        handleTimeout()
      }
    }, 250)

    return () => clearInterval(timerRef.current)
    // handleTimeout reads the latest round/attempt state from refs.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [currentRound, attemptId, result])

  useEffect(() => {
    if (!roundLocked && !submitting) {
      inputRef.current?.focus()
    }
  }, [roundLocked, submitting, currentIndex])

  useEffect(() => {
    const handleVisibilityChange = () => {
      if (document.hidden && attemptId && !result) {
        if (!visibilityAlertShownRef.current) {
          visibilityAlertShownRef.current = true
          alert("Attenzione: durante il minigioco non dovresti cambiare scheda o finestra.")
        }
      } else {
        visibilityAlertShownRef.current = false
      }
    }

    document.addEventListener("visibilitychange", handleVisibilityChange)

    return () => {
      document.removeEventListener("visibilitychange", handleVisibilityChange)
    }
  }, [attemptId, result])

  async function goNextOrFinish() {
    const minigiocoData = minigiocoRef.current
    const index = currentIndexRef.current

    if (!minigiocoData) return

    const isLast = index >= minigiocoData.rounds.length - 1

    if (isLast) {
      await finishMinigioco()
      return
    }

    setCurrentIndex((prev) => {
      const next = prev + 1
      currentIndexRef.current = next
      return next
    })
  }

  async function handleTimeout() {
    const round = currentRoundRef.current

    if (!round || !attemptId || finishingRef.current) return
    if (roundLockedRef.current) return

    setRoundLockedSafe(true)
    setSubmittingSafe(true)
    clearInterval(timerRef.current)

    try {
      const maxTimeMs = Number(round.time_limit_seconds || 0) * 1000

      const res = await fetch(`${API_BASE}/api/minigiochi/answer`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          attempt_id: attemptId,
          round_id: round.id,
          risposta: null,
          time_taken: maxTimeMs,
        }),
      })

      const data = await res.json()

      if (!res.ok) {
        setError(data.message || "Errore salvataggio timeout")
        return
      }

      setFeedback({ type: "wrong", message: "Tempo scaduto!" })
      await new Promise((resolve) => setTimeout(resolve, 500))
      await goNextOrFinish()
    } catch (err) {
      logError(err)
      setError("Errore di connessione")
    } finally {
      setSubmittingSafe(false)
    }
  }

  async function handleSubmit(e) {
    e.preventDefault()

    const round = currentRoundRef.current

    if (!round || !attemptId || finishingRef.current) return
    if (submittingRef.current || roundLockedRef.current) return
    if (!inputValue.trim()) return

    setSubmittingSafe(true)

    try {
      const maxTimeMs = Number(round.time_limit_seconds || 0) * 1000
      const elapsedMs = Math.min(Date.now() - roundStartedAt, maxTimeMs)

      const res = await fetch(`${API_BASE}/api/minigiochi/answer`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          attempt_id: attemptId,
          round_id: round.id,
          risposta: inputValue.trim(),
          time_taken: elapsedMs,
        }),
      })

      const data = await res.json()

      if (!res.ok) {
        setError(data.message || "Errore nel salvataggio della risposta")
        return
      }

      setTentativiFalliti(data.tentativi_falliti || 0)

      if (data.correct) {
        setRoundLockedSafe(true)
        clearInterval(timerRef.current)
        setFeedback({ type: "correct", message: "Corretto!" })
        await new Promise((resolve) => setTimeout(resolve, 500))
        await goNextOrFinish()
      } else {
        setFeedback({ type: "wrong", message: "Sbagliato, riprova!" })
        setInputValue("")
        setSubmittingSafe(false)
      }
    } catch (err) {
      logError(err)
      setError("Errore di connessione")
      setSubmittingSafe(false)
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

  async function finishMinigioco() {
    if (!attemptId || finishingRef.current) return

    finishingRef.current = true
    clearInterval(timerRef.current)

    try {
      const res = await fetch(`${API_BASE}/api/minigiochi/finish`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          attempt_id: attemptId,
        }),
      })

      const data = await res.json()

      if (!res.ok) {
        setError(data.message || "Errore nella chiusura del minigioco")
        return
      }

      setResult({
        score: data.score,
      })
    } catch (err) {
      logError(err)
      setError("Errore di connessione durante la chiusura del minigioco")
    } finally {
      finishingRef.current = false
    }
  }

  function formatSeconds(seconds) {
    return `${seconds}s`
  }

  function handleBackToMinigiochi() {
    clearInterval(timerRef.current)
    navigate("/minigiochi")
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
