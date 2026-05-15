import { useState } from "react"
import { Link, useNavigate, useSearchParams } from "react-router-dom"
import { useEffect } from "react"
import axios from "axios"
import css from "./Login.module.css"
import { useAuth } from "../../context/AuthContext"
import LoaderButton from "../../components/LoaderButton"

function Login() {
  const navigate = useNavigate()
  const { login: authLogin } = useAuth()

  const [form, setForm] = useState({
    login: "",
    password: ""
  })

  const [searchParams] = useSearchParams()
  const [success, setSuccess] = useState(null)

  useEffect(() => {
    if (searchParams.get("verified")) {
      setSuccess("Email verificata con successo 🎉 Ora puoi accedere.")

      // 🔥 pulisci URL
      window.history.replaceState({}, document.title, "/login")
    }
  }, [searchParams])

  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(false)

  const [showPassword, setShowPassword] = useState(false)

  const handleChange = (e) => {
    setForm((prev) => ({
      ...prev,
      [e.target.name]: e.target.value
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()

    setError(null)

    if (!form.login.trim() || !form.password.trim()) {
      setError("Inserisci email o nickname e password")
      return
    }

    try {
      setLoading(true)

      const res = await axios.post("/api/login", form)

      const { token, user } = res.data

      authLogin(user, token)

      navigate("/dashboard")
    } catch (err) {
      console.log("ERRORE LOGIN:", err)

      if (err.response?.status === 401) {
        setError("Credenziali non valide")
      } else if (err.response?.status === 403) {
        setError("Devi verificare la tua email prima di accedere")
      } else if (err.response?.status === 422) {
        setError("Controlla i dati inseriti")
      } else {
        setError("Errore del server. Riprova più tardi.")
      }
    } finally {
      setLoading(false)

    }
  }

  return (
    <div className={`${css.loginPage} container`}>
      <div className="row justify-content-center">
        <div className="col-md-5">
          <div className={`card shadow ${css.loginCard}`}>
            <div className="card-body">
              <h3 className="mb-4 text-center">
                Accedi
              </h3>

              <form onSubmit={handleSubmit}>
                <div className="mb-3">
                  <label className="form-label">
                    Email o Nickname
                  </label>

                  <input
                    className="form-control"
                    name="login"
                    value={form.login}
                    onChange={handleChange}
                    placeholder="email o nickname"
                    autoComplete="username"
                    disabled={loading}
                  />
                </div>

                <div className="mb-2">
                  <label className="form-label">
                    Password
                  </label>

                  <div className={css.passwordWrapper}>
                    <input
                      type={showPassword ? "text" : "password"}
                      className="form-control"
                      name="password"
                      value={form.password}
                      onChange={handleChange}
                      autoComplete="current-password"
                      disabled={loading}
                    />

                    <button
                      type="button"
                      className={css.passwordToggle}
                      onClick={() => setShowPassword((prev) => !prev)}
                      disabled={loading}
                      aria-label={showPassword ? "Nascondi password" : "Mostra password"}
                    >
                      <i className={`bi ${showPassword ? "bi-eye-slash" : "bi-eye"}`}></i>
                    </button>
                  </div>
                </div>

                <div className="text-end mb-3">
                  <Link to="/forgot-password" className={css.forgotLink}>
                    Password dimenticata?
                  </Link>
                </div>

                {error && (
                  <div className="alert alert-danger mb-3">
                    {error}
                  </div>
                )}

                {success && (
                  <div className="alert alert-success mb-3">
                    {success}
                  </div>
                )}

                <LoaderButton
                  type="submit"
                  loading={loading}
                  className="btn btn-primary w-100"
                >
                  Accedi
                </LoaderButton>

                <div className="text-center mt-3">
                  Non hai un account? <Link to="/register">Registrati</Link>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Login