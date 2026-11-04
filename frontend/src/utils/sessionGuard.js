import axios from "axios"
import api, { API_BASE } from "../service/api"

// Rotte in cui un 401 e' un normale esito (credenziali errate, ecc.), non una
// sessione scaduta.
const PUBLIC_AUTH_PATHS = ["/login", "/logout", "/register", "/forgot-password", "/reset-password"]

let handling = false

function isApiUrl(url) {
  if (typeof url !== "string") return false
  return url.startsWith(`${API_BASE}/api`) || url.startsWith("/api")
}

function isPublicAuthPath(url) {
  return PUBLIC_AUTH_PATHS.some((p) => url.includes(`/api${p}`))
}

function readAuthHeader(headers) {
  if (!headers) return null
  if (typeof headers.get === "function") {
    return headers.get("Authorization") ?? headers.get("authorization")
  }
  return headers.Authorization ?? headers.authorization ?? null
}

// Il token API e' scaduto o revocato ma l'interfaccia, che si fida di
// localStorage, mostra ancora l'utente come loggato: si ripulisce tutto e si
// riporta l'utente al login invece di lasciargli errori "Unauthenticated".
function handleExpiredSession() {
  if (handling) return
  handling = true

  try {
    localStorage.removeItem("token")
    localStorage.removeItem("user")
    sessionStorage.setItem("sessionExpired", "1")
  } catch {
    // storage non disponibile: si prosegue comunque col redirect
  }

  window.location.replace("/login")
}

function axiosGuard(instance) {
  instance.interceptors.response.use(
    (response) => response,
    (error) => {
      const status = error.response?.status
      const url = error.config?.url ?? ""
      const base = error.config?.baseURL ?? ""
      const fullUrl = url.startsWith("http") ? url : `${base}${url}`

      if (
        status === 401 &&
        readAuthHeader(error.config?.headers) &&
        isApiUrl(fullUrl) &&
        !isPublicAuthPath(fullUrl)
      ) {
        handleExpiredSession()
      }

      return Promise.reject(error)
    }
  )
}

export function installSessionGuard() {
  axiosGuard(axios)
  axiosGuard(api)

  const originalFetch = window.fetch.bind(window)

  window.fetch = async (input, init) => {
    const response = await originalFetch(input, init)

    if (response.status === 401) {
      const url = typeof input === "string" ? input : input?.url
      const auth = readAuthHeader(init?.headers) ?? readAuthHeader(input?.headers)

      if (auth && isApiUrl(url) && !isPublicAuthPath(url)) {
        handleExpiredSession()
      }
    }

    return response
  }
}
