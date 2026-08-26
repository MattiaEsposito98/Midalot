import { useEffect, useMemo, useRef, useState } from "react"
import css from "./QuestionAudio.module.css"

function formatTime(seconds) {
  const total = Math.max(0, Number(seconds) || 0)
  const mins = Math.floor(total / 60)
  const secs = Math.floor(total % 60)
  return `${mins}:${String(secs).padStart(2, "0")}`
}

/**
 * Player audio di una domanda.
 *
 * Se l'admin ha scelto un intervallo (start/end) viene riprodotto solo quello:
 * mostriamo un player compatto che non permette di uscire dall'intervallo.
 * Senza intervallo resta il player nativo del browser, come prima.
 */
function QuestionAudio({ src, startSeconds, endSeconds, className }) {
  const audioRef = useRef(null)
  const [playing, setPlaying] = useState(false)
  const [elapsed, setElapsed] = useState(0)

  const range = useMemo(() => {
    const start = Number(startSeconds)
    const end = Number(endSeconds)

    if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) {
      return null
    }

    return { start, end, span: end - start }
  }, [startSeconds, endSeconds])

  useEffect(() => {
    setPlaying(false)
    setElapsed(0)
  }, [src, range])

  useEffect(() => {
    const audio = audioRef.current

    if (!audio || !range) return

    function handleTimeUpdate() {
      if (audio.currentTime >= range.end) {
        audio.pause()
        audio.currentTime = range.start
        setPlaying(false)
        setElapsed(range.span)
        return
      }

      setElapsed(Math.max(0, audio.currentTime - range.start))
    }

    function handleEnded() {
      setPlaying(false)
      setElapsed(range.span)
    }

    audio.addEventListener("timeupdate", handleTimeUpdate)
    audio.addEventListener("ended", handleEnded)

    return () => {
      audio.removeEventListener("timeupdate", handleTimeUpdate)
      audio.removeEventListener("ended", handleEnded)
    }
  }, [range])

  if (!src) return null

  if (!range) {
    return (
      <audio controls className={className}>
        <source src={src} type="audio/mp4" />
      </audio>
    )
  }

  function togglePlay() {
    const audio = audioRef.current
    if (!audio) return

    if (playing) {
      audio.pause()
      setPlaying(false)
      return
    }

    if (audio.currentTime < range.start || audio.currentTime >= range.end) {
      audio.currentTime = range.start
    }

    audio.play()
      .then(() => setPlaying(true))
      .catch(() => setPlaying(false))
  }

  const progressPercent = Math.min(100, (elapsed / range.span) * 100)

  return (
    <div className={css.player}>
      <audio ref={audioRef} preload="metadata" src={src} />

      <button
        type="button"
        className={css.playBtn}
        onClick={togglePlay}
        aria-label={playing ? "Metti in pausa" : "Ascolta l'estratto"}
      >
        <i className={`bi ${playing ? "bi-pause-fill" : "bi-play-fill"}`}></i>
      </button>

      <div className={css.bar}>
        <div className={css.barFill} style={{ width: `${progressPercent}%` }} />
      </div>

      <span className={css.time}>
        {formatTime(elapsed)} / {formatTime(range.span)}
      </span>
    </div>
  )
}

export default QuestionAudio
