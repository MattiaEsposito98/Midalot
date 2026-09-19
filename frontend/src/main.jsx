import React from "react"
import ReactDOM from "react-dom/client"
import { BrowserRouter } from "react-router-dom"
import "bootstrap/dist/css/bootstrap.min.css"
import "bootstrap/dist/js/bootstrap.bundle.min.js"
import "bootstrap-icons/font/bootstrap-icons.css"
import "./index.css"

import App from "./App"
import { AuthProvider } from "./context/AuthContext"
import { CookieConsentProvider } from "./context/CookieConsentContext"

ReactDOM.createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <BrowserRouter>
      <CookieConsentProvider>
        <AuthProvider>
          <App />
        </AuthProvider>
      </CookieConsentProvider>
    </BrowserRouter>
  </React.StrictMode>
)
