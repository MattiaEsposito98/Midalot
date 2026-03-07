import { createContext, useContext, useEffect, useState } from "react"

const AuthContext = createContext()

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [token, setToken] = useState(null)

  useEffect(() => {
    const savedUser = localStorage.getItem("user")
    const savedToken = localStorage.getItem("token")

    if (savedUser) {
      setUser(JSON.parse(savedUser))
    }

    if (savedToken) {
      setToken(savedToken)
    }
  }, [])

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

export function useAuth() {
  return useContext(AuthContext)
}