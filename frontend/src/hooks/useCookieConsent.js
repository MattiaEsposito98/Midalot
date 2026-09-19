import { useContext } from "react"
import { CookieConsentContext } from "../context/cookieConsentStore"

export function useCookieConsent() {
  return useContext(CookieConsentContext)
}
