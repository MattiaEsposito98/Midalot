import { useEffect, useMemo, useRef, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "../Quiz/Quiz.module.css"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"

const API_URL = import.meta.env.VITE_API_URL

function shuffleArray(array) {
  const copy = [...array]

  for (let i = copy.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[copy[i], copy[j]] = [copy[j], copy[i]]
  }

  return copy
}

function TrainingPlay() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { token } = useAuth()

  const [quiz, setQuiz] = useState(null)
  const [attemptId, setAttemptId] = useState(null)
  const [sessionToken, setSessionToken] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [currentIndex, setCurrentIndex] = useState(0)
  const [timeLeft, setTimeLeft] = useState(0)
  const [questionStartedAt, setQuestionStartedAt] = useState(null)
  const [selectedAnswerId, setSelectedAnswerId] = useState(null)
  const [answerFeedback, setAnswerFeedback] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [result, setResult] = useState(null)
  const [questionLocked, setQuestionLocked] = useState(false)

  const timerRef = useRef(null)
  const timeoutTriggeredRef = useRef(false)
  const finishingRef = useRef(false)
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
    async function initTraining() {
      try {
        setLoading(true)
        setError("")

        const url = token
          ? `${API_URL}/api/training/quizzes/${id}/start`
          : `${API_URL}/api/training/quizzes/${id}/guest-start`

        const res = await fetch(url, {
          method: "POST",
          headers: {
            Accept: "application/json",
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
          },
        })

        const data = await res.json()

        if (!res.ok) {
          setError(data.message || "Training non disponibile")
          return
        }

        setQuiz(data.quiz)
        setAttemptId(data.attempt_id || null)
        setSessionToken(data.session_token || null)
      } catch (err) {
        logError(err)
        setError("Errore di connessione")
      } finally {
        setLoading(false)
      }
    }

    initTraining()

    return () => clearInterval(timerRef.current)
  }, [id, token])

  useEffect(() => {
    if (!currentQuestion || result) return

    clearInterval(timerRef.current)

    const maxTime = Number(currentQuestion.time_limit_seconds || 0)
    const startedAt = Date.now()
    const deadline = startedAt + maxTime * 1000

    setTimeLeft(maxTime)
    setSelectedAnswerId(null)
    setAnswerFeedback(null)
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
    // handleTimeout reads the latest question/session state from refs.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [currentQuestion, result])

  async function goNextOrFinish() {
    const quizData = quizRef.current
    const index = currentIndexRef.current

    if (!quizData) return

    if (index >= quizData.questions.length - 1) {
      await finishTraining()
      return
    }

    setCurrentIndex((prev) => {
      const next = prev + 1
      currentIndexRef.current = next
      return next
    })
  }

  async function submitAnswer(answerId, timeTaken) {
    const question = currentQuestionRef.current

    if (!question || finishingRef.current) return
    if (submittingRef.current || questionLockedRef.current) return

    setQuestionLockedSafe(true)
    setSubmittingSafe(true)
    clearInterval(timerRef.current)

    try {
      const res = await fetch(token ? `${API_URL}/api/training/answer` : `${API_URL}/api/training/guest-answer`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify({
          ...(token ? { attempt_id: attemptId } : { session_token: sessionToken }),
          question_id: question.id,
          answer_id: answerId,
          time_taken: timeTaken,
        }),
      })

      const data = await res.json()

      if (!res.ok) {
        setError(data.message || "Errore nel salvataggio della risposta")
        return
      }

      setAnswerFeedback(data)
      await new Promise((resolve) => setTimeout(resolve, 1500))
      await goNextOrFinish()
    } catch (err) {
      logError(err)
      setError("Errore di connessione")
    } finally {
      setSubmittingSafe(false)
    }
  }

  async function handleTimeout() {
    const question = currentQuestionRef.current
    if (!question) return

    const maxTimeMs = Number(question.time_limit_seconds || 0) * 1000
    await submitAnswer(null, maxTimeMs)
  }

  async function handleAnswer(answerId) {
    const question = currentQuestionRef.current
    if (!question || selectedAnswerId) return

    setSelectedAnswerId(answerId)
    const maxTimeMs = Number(question.time_limit_seconds || 0) * 1000
    const elapsedMs = Math.min(Date.now() - questionStartedAt, maxTimeMs)
    await submitAnswer(answerId, elapsedMs)
  }

  function getAnswerClassName(answerId) {
    if (!answerFeedback) {
      return selectedAnswerId === answerId ? styles.answerSelected : ""
    }

    if (Number(answerFeedback.correct_answer_id) === Number(answerId)) {
      return styles.answerCorrect
    }

    if (Number(selectedAnswerId) === Number(answerId) && answerFeedback.wrong) {
      return styles.answerWrong
    }

    return ""
  }

  function getFeedbackMessage() {
    if (!answerFeedback) return null
    if (answerFeedback.correct) return "Risposta corretta!"
    if (answerFeedback.timeout) return "Tempo scaduto. La risposta corretta è evidenziata in verde."
    return "Risposta sbagliata. La risposta corretta è evidenziata in verde."
  }

  async function finishTraining() {
    if (finishingRef.current) return

    finishingRef.current = true
    clearInterval(timerRef.current)

    try {
      const res = await fetch(token ? `${API_URL}/api/training/finish` : `${API_URL}/api/training/guest-finish`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify(token ? { attempt_id: attemptId } : { session_token: sessionToken }),
      })

      const data = await res.json()

      if (!res.ok) {
        setError(data.message || "Errore nella chiusura del training")
        return
      }

      setResult(data)
    } catch (err) {
      logError(err)
      setError("Errore di connessione")
    } finally {
      finishingRef.current = false
    }
  }

  if (loading) {
    return (
      <div className={styles.centerBox}>
        <div className="spinner-border text-primary"></div>
      </div>
    )
  }

  if (error) {
    return (
      <div className={styles.centerBox}>
        <div className={styles.errorCard}>
          <h2 className={styles.errorTitle}>Attenzione</h2>
          <p className={styles.errorText}>{error}</p>
          <button className="btn btn-primary" onClick={() => navigate("/training")}>
            Torna al training
          </button>
        </div>
      </div>
    )
  }

  if (result) {
    return (
      <div className={styles.centerBox}>
        <div className={styles.resultCard}>
          <h1 className={styles.resultTitle}>Training completato</h1>
          <p className={styles.resultSubtitle}>
            Risposte corrette: {result.correct_answers}/{quiz.total_questions}
          </p>
          <div className={styles.scoreBox}>
            <span className={styles.scoreLabel}>Punteggio finale</span>
            <strong className={styles.scoreValue}>{formatQuizScore(result.score)}</strong>
          </div>
          {!token && (
            <div className="alert alert-info">
              Registrati per salvare le tue sessioni di training e vedere i tuoi progressi.
            </div>
          )}
          <div className="d-grid gap-2">
            <Link className="btn btn-primary" to={`/training/${quiz.category.slug}`}>
              Torna alla categoria
            </Link>
            {!token && <Link className="btn btn-outline-secondary" to="/register">Registrati</Link>}
          </div>
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
              Training / {quiz.category.name} / Domanda {currentIndex + 1} di {quiz.total_questions}
            </p>
          </div>

          <div className={styles.timerCard}>
            <span className={styles.timerLabel}>Tempo rimasto</span>
            <strong className={styles.timerValue}>{timeLeft}s</strong>
          </div>
        </div>

        <div className={styles.progressWrap}>
          <div className={styles.progressLabelRow}>
            <span>Avanzamento training</span>
            <span>{Math.round(progressPercent)}%</span>
          </div>
          <div className={styles.progressBar}>
            <div className={styles.progressFill} style={{ width: `${progressPercent}%` }} />
          </div>
        </div>

        <div className={styles.questionCard}>
          <div className={styles.timeBarWrap}>
            <div className={styles.timeBar}>
              <div className={styles.timeBarFill} style={{ width: `${timePercent}%` }} />
            </div>
          </div>

          <div className={styles.questionHeader}>
            <h2 className={styles.questionText}>{currentQuestion.question_text}</h2>
          </div>

          {currentQuestion.image && (
            <div className={styles.mediaWrap}>
              <img src={currentQuestion.image} alt="Domanda" className={styles.image} draggable="false" />
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

          {answerFeedback && (
            <div
              className={`${styles.answerFeedback} ${
                answerFeedback.correct ? styles.feedbackCorrect : styles.feedbackWrong
              }`}
            >
              {getFeedbackMessage()}
            </div>
          )}

          <div className={styles.answersGrid}>
            {shuffledAnswers.map((answer, index) => (
              <button
                key={answer.id}
                type="button"
                className={`${styles.answerBtn} ${getAnswerClassName(answer.id)}`}
                onClick={() => handleAnswer(answer.id)}
                disabled={submitting || !!selectedAnswerId || questionLocked}
              >
                <span className={styles.answerIndex}>{String.fromCharCode(65 + index)}</span>
                <span className={styles.answerText}>{answer.answer_text}</span>
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}

export default TrainingPlay
