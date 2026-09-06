import { useEffect, useState } from "react"
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

  useEffect(() => {
    // Il token API resta valido per giorni: chi apre il sito con una
    // sessione gia' attiva non passa mai da login(), quindi senza questa
    // chiamata il bonus giornaliero scatterebbe solo il giorno in cui
    // l'utente reinserisce le credenziali. Qui si segnala "sono entrato
    // oggi" ad ogni apertura dell'app.
    const existingToken = localStorage.getItem("token")
    if (!existingToken) return

    fetch(`${API_BASE}/api/daily-bonus`, {
      method: "POST",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${existingToken}`,
      },
    }).catch(logError)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

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
    <AuthContext.Provider value={{ user, token, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

