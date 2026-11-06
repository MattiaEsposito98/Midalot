import { useCallback, useEffect, useState } from "react"
import { AuthContext } from "./authStore"
import { API_BASE } from "../service/api"
import { logError } from "../utils/logger"

export function AuthProvider({ children }) {

  const [user, setUser] = useState(() => {
    const savedUser = localStorage.getItem("user")

    if (!savedUser) return null

    try {
      return JSON.parse(savedUser)
    } catch {
      localStorage.removeItem("user")
      localStorage.removeItem("token")
      return null
    }
  })

  const [token, setToken] = useState(() => {
    return localStorage.getItem("token")
  })

  // Aggiorna il blocco XP dell'utente (dopo una partita o il bonus
  // giornaliero) mantenendo allineato anche localStorage.
  const updateXp = useCallback((xp) => {
    if (!xp) return

    setUser((prev) => {
      if (!prev) return prev
      const next = { ...prev, xp_info: xp }
      // Dopo una partita il backend manda anche il nuovo saldo di monete.
      if (xp.coins != null) next.coins = xp.coins
      localStorage.setItem("user", JSON.stringify(next))
      return next
    })
  }, [])

  // Aggiorna alcuni campi dell'utente (es. avatar_url dopo il salvataggio dell'avatar).
  const updateUser = useCallback((patch) => {
    setUser((prev) => {
      if (!prev) return prev
      const next = { ...prev, ...patch }
      localStorage.setItem("user", JSON.stringify(next))
      return next
    })
  }, [])

  // Ricarica il profilo dal server: l'utente salvato in localStorage puo'
  // essere vecchio (es. senza il blocco XP introdotto dopo il suo login).
  const refreshUser = useCallback(async () => {
    const existingToken = localStorage.getItem("token")
    if (!existingToken) return

    try {
      const res = await fetch(`${API_BASE}/api/user`, {
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${existingToken}`,
        },
      })

      if (!res.ok) return

      const fresh = await res.json()
      localStorage.setItem("user", JSON.stringify(fresh))
      setUser(fresh)
    } catch (err) {
      logError(err)
    }
  }, [])

  useEffect(() => {
    // Sincronizzazione con il server all'avvio: il setState avviene dopo la fetch.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    refreshUser()
  }, [refreshUser])

  useEffect(() => {
    // Il token API resta valido per giorni: chi apre il sito con una
    // sessione gia' attiva non passa mai da login(), quindi senza questa
    // chiamata il bonus giornaliero scatterebbe solo il giorno in cui
    // l'utente reinserisce le credenziali. Qui si segnala "sono entrato
    // oggi" ad ogni apertura dell'app e quando la scheda torna visibile in
    // un giorno nuovo (scheda lasciata aperta oltre la mezzanotte).
    let lastBonusDay = null

    const claimDailyBonus = async () => {
      const existingToken = localStorage.getItem("token")
      if (!existingToken) return

      const today = new Date().toDateString()
      if (lastBonusDay === today) return
      lastBonusDay = today

      try {
        const res = await fetch(`${API_BASE}/api/daily-bonus`, {
          method: "POST",
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${existingToken}`,
          },
        })

        if (!res.ok) {
          lastBonusDay = null
          return
        }

        // Il backend rinnova i token piu' vecchi di qualche giorno, cosi' chi
        // entra regolarmente non scade mai a 30 giorni dal primo login.
        const data = await res.json()
        updateXp(data.xp)
        if (data.token && localStorage.getItem("token") === existingToken) {
          localStorage.setItem("token", data.token)
          setToken(data.token)
        }
      } catch (err) {
        lastBonusDay = null
        logError(err)
      }
    }

    const onVisible = () => {
      if (document.visibilityState === "visible") claimDailyBonus()
    }

    claimDailyBonus()
    document.addEventListener("visibilitychange", onVisible)

    return () => document.removeEventListener("visibilitychange", onVisible)
  }, [updateXp])

  const login = (userData, tokenData) => {

    setUser(userData)
    setToken(tokenData)

    localStorage.setItem("user", JSON.stringify(userData))
    localStorage.setItem("token", tokenData)
  }

  const logout = async () => {
    const currentToken = localStorage.getItem("token")

    // Prima si svuota lo stato locale, cosi' l'utente esce subito anche se la
    // rete e' lenta o assente.
    setUser(null)
    setToken(null)
    localStorage.removeItem("user")
    localStorage.removeItem("token")

    if (!currentToken) return

    // Poi si revoca il token lato server: senza questa chiamata resterebbe
    // valido anche dopo il logout.
    try {
      await fetch(`${API_BASE}/api/logout`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${currentToken}`,
        },
      })
    } catch (err) {
      logError(err)
    }
  }

  return (
    <AuthContext.Provider value={{ user, token, login, logout, updateXp, updateUser, refreshUser }}>
      {children}
    </AuthContext.Provider>
  )
}

