import { useEffect, useMemo, useRef, useState } from "react"
import { useNavigate } from "react-router-dom"
import { useAuth } from "../context/useAuth"
import { logError } from "../utils/logger"
import { API_BASE } from "../service/api"

/**
 * Stato e logica condivisa da tutti i tipi di minigioco: caricamento,
 * avvio tentativo, timer per round, invio risposta, avanzamento/chiusura,
 * anti-cheat cambio tab. Ogni componente di gioco (TastieraRotta,
 * SaltoTemporale, TrovaIntruso) usa questo hook e renderizza solo
 * l'interazione specifica del round corrente.
 *
 * `retryOnWrong`: true per Tastiera Rotta (si può ritentare finché il
 * tempo non scade), false per i giochi a tentativo singolo (una risposta
 * sbagliata chiude subito il round, come una risposta corretta).
 */
export function useMinigiocoAttempt(id, { retryOnWrong = false } = {}) {
  const navigate = useNavigate()
  const { token } = useAuth()

  const [minigioco, setMinigioco] = useState(null)
  const [attemptId, setAttemptId] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [currentIndex, setCurrentIndex] = useState(0)
  const [timeLeft, setTimeLeft] = useState(0)
  const [submitting, setSubmitting] = useState(false)
  const [result, setResult] = useState(null)
  const [roundLocked, setRoundLocked] = useState(false)
  const [feedback, setFeedback] = useState(null)

  const timerRef = useRef(null)
  const timeoutTriggeredRef = useRef(false)
  const finishingRef = useRef(false)
  const visibilityAlertShownRef = useRef(false)
  const currentRoundRef = useRef(null)
  const currentIndexRef = useRef(0)
  const roundStartedAtRef = useRef(null)
  const roundDeadlineRef = useRef(null)
  const submittingRef = useRef(false)
  const roundLockedRef = useRef(false)
  const minigiocoRef = useRef(null)
  const initedKeyRef = useRef(null)
  const submitAnswerRef = useRef(null)

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
    roundStartedAtRef.current = startedAt
    setFeedback(null)
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
        submitAnswerRef.current?.(null)
      }
    }, 250)

    return () => clearInterval(timerRef.current)
  }, [currentRound, attemptId, result])

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

  /**
   * Invia la risposta del round corrente. `risposta` può essere una
   * stringa (Tastiera Rotta), un array di ID (Salto Temporale), un ID
   * singolo (Trova l'Intruso) o `null` (timeout, gestito internamente).
   * Ritorna il payload di risposta dell'API, o `null` se non inviata.
   */
  async function submitAnswer(risposta) {
    const round = currentRoundRef.current
    const isTimeout = risposta === null

    if (!round || !attemptId || finishingRef.current) return null
    if (roundLockedRef.current) return null
    if (!isTimeout && submittingRef.current) return null

    if (isTimeout) {
      setRoundLockedSafe(true)
      clearInterval(timerRef.current)
    }

    setSubmittingSafe(true)

    try {
      const maxTimeMs = Number(round.time_limit_seconds || 0) * 1000
      const elapsedMs = isTimeout
        ? maxTimeMs
        : Math.min(Date.now() - roundStartedAtRef.current, maxTimeMs)

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
          risposta,
          time_taken: elapsedMs,
        }),
      })

      const data = await res.json()

      if (!res.ok) {
        setError(data.message || "Errore nel salvataggio della risposta")
        setSubmittingSafe(false)
        return null
      }

      const shouldLock = isTimeout || data.correct || !retryOnWrong

      if (data.correct) {
        setFeedback({ type: "correct", message: "Corretto!" })
      } else if (isTimeout) {
        setFeedback({ type: "wrong", message: "Tempo scaduto!" })
      } else {
        setFeedback({ type: "wrong", message: retryOnWrong ? "Sbagliato, riprova!" : "Sbagliato!" })
      }

      if (shouldLock) {
        setRoundLockedSafe(true)
        clearInterval(timerRef.current)
        await new Promise((resolve) => setTimeout(resolve, 500))
        await goNextOrFinish()
      } else {
        setSubmittingSafe(false)
      }

      return data
    } catch (err) {
      logError(err)
      setError("Errore di connessione")
      setSubmittingSafe(false)
      return null
    }
  }

  submitAnswerRef.current = submitAnswer

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

      setResult({ score: data.score })
    } catch (err) {
      logError(err)
      setError("Errore di connessione durante la chiusura del minigioco")
    } finally {
      finishingRef.current = false
    }
  }

  function handleBackToMinigiochi() {
    clearInterval(timerRef.current)
    navigate("/minigiochi")
  }

  return {
    minigioco,
    currentRound,
    currentIndex,
    attemptId,
    loading,
    error,
    result,
    submitting,
    roundLocked,
    feedback,
    timeLeft,
    submitAnswer,
    handleBackToMinigiochi,
  }
}
