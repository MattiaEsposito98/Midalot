import { useEffect, useMemo, useRef, useState } from "react"
import { useNavigate, useParams } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "./Quiz.module.css"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import { API_BASE } from "../../service/api"

function shuffleArray(array) {
  const copy = [...array]

  for (let i = copy.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [copy[i], copy[j]] = [copy[j], copy[i]];
  }

  return copy
}

function Quiz() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { token } = useAuth()

  const [quiz, setQuiz] = useState(null)
  const [attemptId, setAttemptId] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [currentIndex, setCurrentIndex] = useState(0)
  const [timeLeft, setTimeLeft] = useState(0)
  const [questionStartedAt, setQuestionStartedAt] = useState(null)
  const [selectedAnswerId, setSelectedAnswerId] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [result, setResult] = useState(null)
  const [questionLocked, setQuestionLocked] = useState(false)

  const timerRef = useRef(null)
  const timeoutTriggeredRef = useRef(false)
  const finishingRef = useRef(false)
  const visibilityAlertShownRef = useRef(false)
  const currentQuestionRef = useRef(null)
  const currentIndexRef = useRef(0)
  const questionDeadlineRef = useRef(null)
  const submittingRef = useRef(false)
  const questionLockedRef = useRef(false)
  const quizRef = useRef(null)

  const currentQuestion = useMemo(() => {
    return quiz?.questions?.[currentIndex] || null
  }, [quiz, currentIndex])

  const shuffledAnswers = useMemo(() => {
    if (!currentQuestion?.answers) return []
    return shuffleArray(currentQuestion.answers)
  }, [currentQuestion?.answers])

  function setSubmittingSafe(value) {
    submittingRef.current = value
    setSubmitting(value)
  }

  function setQuestionLockedSafe(value) {
    questionLockedRef.current = value
    setQuestionLocked(value)
  }

  useEffect(() => {
    currentQuestionRef.current = currentQuestion
    currentIndexRef.current = currentIndex
  }, [currentQuestion, currentIndex])

  useEffect(() => {
    quizRef.current = quiz
  }, [quiz])

  useEffect(() => {
    async function initQuiz() {
      try {
        setLoading(true)
        setError("")
        setQuiz(null)
        setAttemptId(null)
        setCurrentIndex(0)
        setResult(null)

        const quizRes = await fetch(`${API_BASE}/api/quizzes/${id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const quizData = await quizRes.json()

        if (!quizRes.ok) {
          setError(quizData.message || "Errore nel caricamento del quiz")
          return
        }

        setQuiz(quizData.quiz)

        const startRes = await fetch(`${API_BASE}/api/quiz/${id}/start`, {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const startData = await startRes.json()

        if (!startRes.ok) {
          if (startData.attempt_id && startData.completed === false) {
            setAttemptId(startData.attempt_id)
            return
          }

          setError(startData.message || "Impossibile avviare il quiz")
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

    initQuiz()

    return () => {
      clearInterval(timerRef.current)
    }
  }, [id, token])

  useEffect(() => {
    if (!currentQuestion || !attemptId || result) return

    clearInterval(timerRef.current)

    const maxTime = Number(currentQuestion.time_limit_seconds || 0)
    const startedAt = Date.now()
    const deadline = startedAt + maxTime * 1000

    setTimeLeft(maxTime)
    setSelectedAnswerId(null)
    setQuestionStartedAt(startedAt)
    setQuestionLockedSafe(false)
    setSubmittingSafe(false)

    timeoutTriggeredRef.current = false
    questionDeadlineRef.current = deadline

    timerRef.current = setInterval(() => {
      const now = Date.now()
      const remainingMs = Math.max(0, questionDeadlineRef.current - now)
      const remainingSeconds = Math.ceil(remainingMs / 1000)

      setTimeLeft(remainingSeconds)

      if (remainingMs <= 0 && !timeoutTriggeredRef.current) {
        timeoutTriggeredRef.current = true
        clearInterval(timerRef.current)
        handleTimeout()
      }
    }, 250)

    return () => clearInterval(timerRef.current)
    // handleTimeout reads the latest question/attempt state from refs.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [currentQuestion, attemptId, result])

  useEffect(() => {
    const blockContextMenu = (e) => {
      e.preventDefault()
    }

    const blockCopy = (e) => {
      e.preventDefault()
    }

    const blockKeys = (e) => {
      const key = e.key.toLowerCase()

      if (
        (e.ctrlKey && key === "c") ||
        (e.ctrlKey && key === "u") ||
        (e.ctrlKey && key === "s") ||
        (e.ctrlKey && key === "a")
      ) {
        e.preventDefault()
      }
    }

    document.addEventListener("contextmenu", blockContextMenu)
    document.addEventListener("copy", blockCopy)
    document.addEventListener("keydown", blockKeys)

    return () => {
      document.removeEventListener("contextmenu", blockContextMenu)
      document.removeEventListener("copy", blockCopy)
      document.removeEventListener("keydown", blockKeys)
    }
  }, [])

  useEffect(() => {
    const handleVisibilityChange = () => {
      if (document.hidden && attemptId && !result) {
        if (!visibilityAlertShownRef.current) {
          visibilityAlertShownRef.current = true
          alert("Attenzione: durante il quiz non dovresti cambiare scheda o finestra.")
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
    const quizData = quizRef.current
    const index = currentIndexRef.current

    if (!quizData) return

    const isLast = index >= quizData.questions.length - 1

    if (isLast) {
      await finishQuiz()
      return
    }

    setCurrentIndex((prev) => {
      const next = prev + 1
      currentIndexRef.current = next
      return next
    })
  }

  async function handleTimeout() {
    const question = currentQuestionRef.current

    if (!question || !attemptId || finishingRef.current) return
    if (submittingRef.current || questionLockedRef.current) return

    setQuestionLockedSafe(true)
    setSubmittingSafe(true)
    clearInterval(timerRef.current)

    try {
      const maxTimeMs = Number(question.time_limit_seconds || 0) * 1000

      const res = await fetch(`${API_BASE}/api/quiz/answer`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          attempt_id: attemptId,
          question_id: question.id,
          answer_id: null,
          time_taken: maxTimeMs,
        }),
      })

      const data = await res.json()

      if (!res.ok) {
        setError(data.message || "Errore salvataggio timeout")
        return
      }

      await goNextOrFinish()
    } catch (err) {
      logError(err)
      setError("Errore di connessione")
    } finally {
      setSubmittingSafe(false)
    }
  }

  async function handleAnswer(answerId) {
    const question = currentQuestionRef.current

    if (!question || !attemptId || finishingRef.current) return
    if (submittingRef.current || questionLockedRef.current || selectedAnswerId) return

    setQuestionLockedSafe(true)
    setSelectedAnswerId(answerId)
    setSubmittingSafe(true)
    clearInterval(timerRef.current)

    try {
      const maxTimeMs = Number(question.time_limit_seconds || 0) * 1000
      const elapsedMs = Math.min(Date.now() - questionStartedAt, maxTimeMs)

      const res = await fetch(`${API_BASE}/api/quiz/answer`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          attempt_id: attemptId,
          question_id: question.id,
          answer_id: answerId,
          time_taken: elapsedMs,
        }),
      })

      const data = await res.json()

      if (!res.ok) {
        setError(data.message || "Errore nel salvataggio della risposta")
        return
      }

      await new Promise((resolve) => setTimeout(resolve, 250))
      await goNextOrFinish()
    } catch (err) {
      logError(err)
      setError("Errore di connessione")
    } finally {
      setSubmittingSafe(false)
    }
  }

  async function finishQuiz() {
    if (!attemptId || finishingRef.current) return

    finishingRef.current = true
    clearInterval(timerRef.current)

    try {
      const res = await fetch(`${API_BASE}/api/quiz/finish`, {
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
        setError(data.message || "Errore nella chiusura del quiz")
        return
      }

      setResult({
        score: data.score,
      })
    } catch (err) {
      logError(err)
      setError("Errore di connessione durante la chiusura del quiz")
    } finally {
      finishingRef.current = false
    }
  }

  function formatSeconds(seconds) {
    return `${seconds}s`
  }

  function handleBackToDashboard() {
    clearInterval(timerRef.current)
    navigate("/dashboard")
  }

  if (loading) {
    return (
      <div className={styles.centerBox}>
        <div className="spinner-border text-primary"></div>
        <p className={styles.centerText}>Caricamento quiz...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className={styles.centerBox}>
        <div className={styles.errorCard}>
          <h2 className={styles.errorTitle}>Attenzione</h2>
          <p className={styles.errorText}>{error}</p>
          <button className="btn btn-primary" onClick={handleBackToDashboard}>
            Torna alla dashboard
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
          <h1 className={styles.resultTitle}>Quiz completato</h1>
          <p className={styles.resultSubtitle}>
            Hai terminato tutte le domande del quiz.
          </p>
          <div className={styles.scoreBox}>
            <span className={styles.scoreLabel}>Punteggio finale</span>
            <strong className={styles.scoreValue}>{formatQuizScore(result.score)}</strong>
          </div>
          <button className="btn btn-primary" onClick={handleBackToDashboard}>
            Torna alla dashboard
          </button>
        </div>
      </div>
    )
  }

  if (!quiz || !currentQuestion) return null

  const progressPercent = ((currentIndex + 1) / quiz.total_questions) * 100
  const timePercent =
    Number(currentQuestion.time_limit_seconds) > 0
      ? Math.max(0, (timeLeft / Number(currentQuestion.time_limit_seconds)) * 100)
      : 0

  return (
    <div className={styles.page}>
      <div className="container">
        <div className={styles.topBar}>
          <div>
            <h1 className={styles.quizTitle}>{quiz.title}</h1>
            <p className={styles.quizSubtitle}>
              Domanda {currentIndex + 1} di {quiz.total_questions}
            </p>
          </div>

          <div className={styles.timerCard}>
            <span className={styles.timerLabel}>Tempo rimasto</span>
            <strong className={styles.timerValue}>{formatSeconds(timeLeft)}</strong>
          </div>
        </div>

        <div className={styles.progressWrap}>
          <div className={styles.progressLabelRow}>
            <span>Avanzamento quiz</span>
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

          <div className={styles.questionHeader}>
            <h2 className={styles.questionText}>
              {currentQuestion.question_text}
            </h2>
          </div>

          {currentQuestion.image && (
            <div className={styles.mediaWrap}>
              <img
                src={currentQuestion.image}
                alt="Domanda"
                className={styles.image}
                draggable="false"
              />
            </div>
          )}

          {currentQuestion.audio && (
            <div className={styles.mediaWrap}>
              <audio controls className={styles.mediaControl}>
                <source src={currentQuestion.audio} />
              </audio>
            </div>
          )}

          {currentQuestion.video && (
            <div className={styles.mediaWrap}>
              <video controls className={styles.video}>
                <source src={currentQuestion.video} />
              </video>
            </div>
          )}

          <div className={styles.answersGrid}>
            {shuffledAnswers.map((answer, index) => (
              <button
                key={answer.id}
                type="button"
                className={`${styles.answerBtn} ${selectedAnswerId === answer.id ? styles.answerSelected : ""}`}
                onClick={() => handleAnswer(answer.id)}
                disabled={submitting || !!selectedAnswerId || questionLocked}
              >
                <span className={styles.answerIndex}>
                  {String.fromCharCode(65 + index)}
                </span>
                <span className={styles.answerText}>{answer.answer_text}</span>
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}

export default Quiz
