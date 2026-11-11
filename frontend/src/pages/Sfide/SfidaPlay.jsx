import { useEffect, useMemo, useRef, useState } from "react"
import { useNavigate, useParams } from "react-router-dom"
import api from "../../service/api"
import styles from "../Quiz/Quiz.module.css"
import { logError } from "../../utils/logger"
import QuestionAudio from "../../components/QuestionAudio/QuestionAudio"
import { notifyChallengesChanged } from "../../hooks/useChallengeCount"
import QuestionCategory from "./QuestionCategory"
import { ensureGuestKey } from "../../utils/guestChallenge"

// Reti mobili instabili: si riprova qualche volta prima di dare errore. Se la
// prima richiesta era in realta' arrivata, il "gia' risposto" conta come successo.
async function postWithRetry(path, body) {
  let lastError

  for (let attempt = 0; attempt < 3; attempt++) {
    try {
      return await api.post(path, body)
    } catch (err) {
      lastError = err
      const status = err.response?.status

      if (status === 403 && /gi(a|à) risposto/.test(err.response?.data?.message || "")) return null
      if (status && status < 500) throw err

      await new Promise((resolve) => setTimeout(resolve, 600))
    }
  }

  throw lastError
}

// Gioco di una sfida: come il Training ma senza mai mostrare se la risposta
// e' giusta, cosi' le soluzioni non escono prima della chiusura della sfida.
// Con guestToken gioca un ospite non registrato (pagina /sfida-amico/:token).
function SfidaPlay({ guestToken, onGuestFinished }) {
  const { id } = useParams()
  const navigate = useNavigate()
  const base = guestToken ? `/guest-challenges/${guestToken}` : `/challenges/${id}`
  const keyRef = useRef(null)
  const withKey = (body = {}) => (guestToken ? { ...body, key: keyRef.current } : body)

  const [questions, setQuestions] = useState([])
  const [currentIndex, setCurrentIndex] = useState(0)
  const [timeLeft, setTimeLeft] = useState(0)
  const [selectedAnswerId, setSelectedAnswerId] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [finishing, setFinishing] = useState(false)

  const timerRef = useRef(null)
  const startedAtRef = useRef(0)
  const lockedRef = useRef(false)
  const finishingRef = useRef(false)
  const questionsRef = useRef([])
  const indexRef = useRef(0)

  const currentQuestion = useMemo(() => questions[currentIndex] || null, [questions, currentIndex])

  useEffect(() => {
    questionsRef.current = questions
    indexRef.current = currentIndex
  }, [questions, currentIndex])

  useEffect(() => {
    let cancelled = false

    async function init() {
      try {
        if (guestToken) keyRef.current = ensureGuestKey(guestToken)
        const res = await api.post(`${base}/start`, guestToken ? { key: keyRef.current } : undefined)
        if (cancelled) return

        const list = res.data.questions
        const answered = new Set(res.data.answered_question_ids)
        const firstTodo = list.findIndex((q) => !answered.has(q.id))

        setQuestions(list)
        setCurrentIndex(firstTodo === -1 ? list.length : firstTodo)
      } catch (err) {
        logError(err)
        if (!cancelled) setError(err.response?.data?.message || "Sfida non disponibile")
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    init()

    return () => {
      cancelled = true
      clearInterval(timerRef.current)
    }
    // base dipende solo da id e guestToken
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id, guestToken])

  async function finishChallenge() {
    if (finishingRef.current) return

    finishingRef.current = true
    setFinishing(true)

    try {
      const res = await api.post(`${base}/finish`, withKey())
      if (guestToken) {
        onGuestFinished?.(res.data)
        return
      }
      notifyChallengesChanged()
      navigate(`/sfide/${id}`, { replace: true })
    } catch (err) {
      logError(err)
      finishingRef.current = false
      setFinishing(false)
      setError(err.response?.data?.message || "Errore nella chiusura della sfida")
    }
  }

  async function submitAnswer(answerId, timeTaken) {
    const question = questionsRef.current[indexRef.current]
    if (!question || lockedRef.current) return

    lockedRef.current = true
    clearInterval(timerRef.current)

    try {
      await postWithRetry(
        `${base}/answer`,
        withKey({
          question_id: question.id,
          answer_id: answerId,
          time_taken: timeTaken,
        })
      )

      await new Promise((resolve) => setTimeout(resolve, 350))

      if (indexRef.current >= questionsRef.current.length - 1) {
        await finishChallenge()
        return
      }

      setCurrentIndex((prev) => prev + 1)
    } catch (err) {
      logError(err)
      setError(err.response?.data?.message || "Errore nel salvataggio della risposta")
    }
  }

  useEffect(() => {
    // Tutte le domande gia' risposte (ripresa di una partita interrotta alla fine).
    if (!loading && !error && questions.length > 0 && currentIndex >= questions.length) {
      finishChallenge()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [loading, error, questions.length, currentIndex])

  useEffect(() => {
    if (!currentQuestion) return

    clearInterval(timerRef.current)

    const maxTime = Number(currentQuestion.time_limit_seconds || 0)
    const startedAt = Date.now()
    const deadline = startedAt + maxTime * 1000
    let timedOut = false

    startedAtRef.current = startedAt
    lockedRef.current = false
    setSelectedAnswerId(null)
    setTimeLeft(maxTime)

    timerRef.current = setInterval(() => {
      const remainingMs = Math.max(0, deadline - Date.now())
      setTimeLeft(Math.ceil(remainingMs / 1000))

      if (remainingMs <= 0 && !timedOut) {
        timedOut = true
        clearInterval(timerRef.current)
        submitAnswer(null, maxTime * 1000)
      }
    }, 250)

    return () => clearInterval(timerRef.current)
    // submitAnswer legge domanda e indice dai ref.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [currentQuestion])

  function handleAnswer(answerId) {
    if (!currentQuestion || selectedAnswerId || lockedRef.current) return

    const maxTimeMs = Number(currentQuestion.time_limit_seconds || 0) * 1000
    const elapsedMs = Math.min(Date.now() - startedAtRef.current, maxTimeMs)

    setSelectedAnswerId(answerId)
    submitAnswer(answerId, elapsedMs)
  }

  if (loading || finishing) {
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
          <button className="btn btn-primary" onClick={() => navigate(guestToken ? "/" : "/sfide")}>
            {guestToken ? "Vai a Midalot" : "Torna alle sfide"}
          </button>
        </div>
      </div>
    )
  }

  if (!currentQuestion) return null

  const progressPercent = ((currentIndex + 1) / questions.length) * 100
  const maxTime = Number(currentQuestion.time_limit_seconds)
  const timePercent = maxTime > 0 ? Math.max(0, (timeLeft / maxTime) * 100) : 0

  return (
    <div className={styles.page}>
      <div className="container">
        <div className={styles.topBar}>
          <div>
            <h1 className={styles.quizTitle}>Sfida</h1>
            <p className={styles.quizSubtitle}>
              Domanda {currentIndex + 1} di {questions.length}
            </p>
          </div>

          <div className={styles.timerCard}>
            <span className={styles.timerLabel}>Tempo rimasto</span>
            <strong className={styles.timerValue}>{timeLeft}s</strong>
          </div>
        </div>

        <div className={styles.progressWrap}>
          <div className={styles.progressLabelRow}>
            <span>Avanzamento</span>
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

          <QuestionCategory category={currentQuestion.category} subcategory={currentQuestion.subcategory} training={currentQuestion.training} />

          {currentQuestion.image && (
            <div className={styles.mediaWrap}>
              <img src={currentQuestion.image} alt="Domanda" className={styles.image} draggable="false" />
            </div>
          )}

          {currentQuestion.audio && (
            <div className={styles.mediaWrap}>
              <QuestionAudio
                src={currentQuestion.audio}
                startSeconds={currentQuestion.audio_start_seconds}
                endSeconds={currentQuestion.audio_end_seconds}
                className={styles.mediaControl}
              />
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
            {currentQuestion.answers.map((answer, index) => (
              <button
                key={answer.id}
                type="button"
                className={`${styles.answerBtn} ${selectedAnswerId === answer.id ? styles.answerSelected : ""}`}
                onClick={() => handleAnswer(answer.id)}
                disabled={!!selectedAnswerId}
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

export default SfidaPlay
