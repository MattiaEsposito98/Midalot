import { useState } from "react"
import axios from "axios"
import { Link } from "react-router-dom"
import LoaderButton from "../../components/LoaderButton"
import css from "../Login/Login.module.css"
import { logError } from "../../utils/logger"

function ForgotPassword() {
  const [email, setEmail] = useState("")
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)

  const handleSubmit = async (e) => {
    e.preventDefault()

    setError(null)
    setSuccess(null)

    if (!email.trim()) {
      setError("Inserisci la tua email")
      return
    }

    try {
      setLoading(true)
      await axios.post("/api/forgot-password", { email })
      setSuccess("Se l'email esiste, ti abbiamo inviato le istruzioni per reimpostare la password.")
      setEmail("")
    } catch (err) {
      logError("ERRORE FORGOT PASSWORD:", err)
      setError("Errore del server. Riprova piu' tardi.")
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className={`${css.loginPage} container`}>
      <div className="row justify-content-center">
        <div className="col-md-6 col-lg-5">
          <div className={`card ${css.loginCard}`}>
            <div className="card-body">
              <div className={css.header}>
                <span className={css.iconBadge}>
                  <i className="bi bi-key-fill"></i>
                </span>
                <h1>Password dimenticata</h1>
                <p>Inserisci la tua email e ti invieremo il link per ripartire.</p>
              </div>

              <form onSubmit={handleSubmit}>
                <div className="mb-3">
                  <label className="form-label">Email</label>
                  <input
                    type="email"
                    className="form-control"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    autoComplete="email"
                    disabled={loading}
                  />
                </div>

                {error && <div className="alert alert-danger">{error}</div>}
                {success && <div className="alert alert-success">{success}</div>}

                <LoaderButton
                  type="submit"
                  loading={loading}
                  className={`btn btn-primary w-100 ${css.submitBtn}`}
                >
                  Invia link di reset
                </LoaderButton>

                <div className={css.switchText}>
                  <Link to="/login">Torna al login</Link>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default ForgotPassword
