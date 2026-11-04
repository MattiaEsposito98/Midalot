import { useEffect, useState } from "react"
import api from "../service/api"

const REFRESH_MS = 60000

// Da emettere dopo aver creato, giocato o chiuso una sfida: aggiorna subito il badge.
export const CHALLENGES_CHANGED = "challenges:changed"

export function notifyChallengesChanged() {
  window.dispatchEvent(new Event(CHALLENGES_CHANGED))
}

// Numero di sfide aperte in cui tocca all'utente giocare (per il badge in navbar).
export function useChallengeCount(enabled) {
  const [count, setCount] = useState(0)

  useEffect(() => {
    if (!enabled) return undefined

    let cancelled = false

    const load = () => {
      api
        .get("/challenges/summary")
        .then((res) => {
          if (!cancelled) setCount(res.data.to_play)
        })
        .catch(() => {})
    }

    load()
    const timer = setInterval(load, REFRESH_MS)
    window.addEventListener(CHALLENGES_CHANGED, load)

    return () => {
      cancelled = true
      clearInterval(timer)
      window.removeEventListener(CHALLENGES_CHANGED, load)
    }
  }, [enabled])

  return enabled ? count : 0
}
