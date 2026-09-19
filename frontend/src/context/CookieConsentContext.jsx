import { useCallback, useState } from "react"
import { CookieConsentContext } from "./cookieConsentStore"

const STORAGE_KEY = "midalot_cookie_consent"

function readConsent() {
  try {
    return localStorage.getItem(STORAGE_KEY)
  } catch {
    return null
  }
}

export function CookieConsentProvider({ children }) {
  const [consent, setConsentState] = useState(readConsent)

  const setConsent = useCallback((value) => {
    try {
      if (value) {
        localStorage.setItem(STORAGE_KEY, value)
      } else {
        localStorage.removeItem(STORAGE_KEY)
      }
    } catch {
      // localStorage non disponibile (es. modalita' privata): il consenso
      // resta valido solo per la sessione corrente.
    }
    setConsentState(value)
  }, [])

  return (
    <CookieConsentContext.Provider value={{ consent, setConsent }}>
      {children}
    </CookieConsentContext.Provider>
  )
}
