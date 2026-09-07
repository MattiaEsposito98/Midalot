import { useState } from "react"
import axios from "axios"
import { Link, useSearchParams } from "react-router-dom"
import LoaderButton from "../../components/LoaderButton"
import css from "../Login/Login.module.css"
import { logError } from "../../utils/logger"
import { API_BASE } from "../../service/api"

function VerificaEmail() {
  const [searchParams] = useSearchParams()
  const stato = searchParams.get("stato")
  const id = searchParams.get("id")

  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)

  const handleResend = async () => {
    setError(null)
    setSuccess(null)

    try {
      setLoading(true)
      const res = await axios.post(`${API_BASE}/api/email/verification-notification/resend`, { id })
      setSuccess(res.data.message)
    } catch (err) {
      logError("ERRORE RINVIO VERIFICA EMAIL:", err)
      setError("Errore del server. Riprova piu' tardi.")
    } finally {
      setLoading(false)
    }
  }

  const isGiaVerificato = stato === "gia-verificato"

  return (
    <div className={`${css.loginPage} container`}>
      <div className="row justify-content-center">
        <div className="col-md-6 col-lg-5">
          <div className={`card ${css.loginCard}`}>
            <div className="card-body">
              <div className={css.header}>
                <span className={css.iconBadge}>
                  <i className={`bi ${isGiaVerificato ? "bi-check-circle-fill" : "bi-clock-history"}`}></i>
                </span>

                {isGiaVerificato ? (
                  <>
                    <h1>Account già verificato</h1>
                    <p>La tua email è già stata verificata: puoi accedere normalmente.</p>
                  </>
                ) : (
                  <>
                    <h1>Link scaduto</h1>
                    <p>Il link di verifica dura 60 minuti ed è scaduto. Richiedine uno nuovo qui sotto.</p>
                  </>
                )}
              </div>

              {error && <div className="alert alert-danger">{error}</div>}
              {success && <div className="alert alert-success">{success}</div>}

              {isGiaVerificato ? (
                <Link to="/login" className={`btn btn-primary w-100 ${css.submitBtn}`}>
                  Vai al login
                </Link>
              ) : (
                <>
                  {!success && (
                    <LoaderButton
                      type="button"
                      loading={loading}
                      onClick={handleResend}
                      className={`btn btn-primary w-100 ${css.submitBtn}`}
                    >
                      Invia di nuovo il link di verifica
                    </LoaderButton>
                  )}

                  <div className={css.switchText}>
                    <Link to="/login">Torna al login</Link>
                  </div>
                </>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default VerificaEmail
