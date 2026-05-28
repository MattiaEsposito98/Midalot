import { useState } from "react"
import { AuthContext } from "./authStore"

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

  const login = (userData, tokenData) => {

    setUser(userData)
    setToken(tokenData)

    localStorage.setItem("user", JSON.stringify(userData))
    localStorage.setItem("token", tokenData)
  }

  const logout = () => {

    setUser(null)
    setToken(null)

    localStorage.removeItem("user")
    localStorage.removeItem("token")
  }

  return (
    <AuthContext.Provider value={{ user, token, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

