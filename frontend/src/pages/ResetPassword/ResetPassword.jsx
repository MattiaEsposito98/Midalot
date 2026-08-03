import { useState } from "react"
import { Link, useNavigate, useSearchParams } from "react-router-dom"
import axios from "axios"
import LoaderButton from "../../components/LoaderButton"
import css from "../Login/Login.module.css"
import { logError } from "../../utils/logger"
import { API_BASE } from "../../service/api"

function ResetPassword() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()

  const token = searchParams.get("token")
  const email = searchParams.get("email")

  const [form, setForm] = useState({
    password: "",
    password_confirmation: ""
  })

  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)

  const handleChange = (e) => {
    setForm((prev) => ({
      ...prev,
      [e.target.name]: e.target.value
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()

    setError(null)
    setSuccess(null)

    if (!form.password || !form.password_confirmation) {
      setError("Inserisci e conferma la password")
      return
    }

    if (form.password !== form.password_confirmation) {
      setError("Le password non coincidono")
      return
    }

    try {
      setLoading(true)

      await axios.post(`${API_BASE}/api/reset-password`, {
        token,
        email,
        password: form.password,
        password_confirmation: form.password_confirmation
      })

      setSuccess("Password aggiornata! Reindirizzamento al login...")

      setTimeout(() => {
        navigate("/login")
      }, 2000)
    } catch (err) {
      logError(err)
      setError(err.response?.data?.message || "Errore durante il reset della password")
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
                  <i className="bi bi-shield-lock-fill"></i>
                </span>
                <h1>Reimposta password</h1>
                <p>Inserisci una nuova password per il tuo account.</p>
              </div>

              <form onSubmit={handleSubmit}>
                <div className="mb-3">
                  <label className="form-label">Nuova password</label>
                  <input
                    type="password"
                    className="form-control"
                    name="password"
                    value={form.password}
                    onChange={handleChange}
                    disabled={loading}
                  />
                </div>

                <div className="mb-3">
                  <label className="form-label">Conferma password</label>
                  <input
                    type="password"
                    className="form-control"
                    name="password_confirmation"
                    value={form.password_confirmation}
                    onChange={handleChange}
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
                  Salva nuova password
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

export default ResetPassword
