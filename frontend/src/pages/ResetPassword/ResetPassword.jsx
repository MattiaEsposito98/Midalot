import { useState } from "react"
import { useSearchParams, useNavigate } from "react-router-dom"
import axios from "axios"
import LoaderButton from "../../components/LoaderButton"

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

      await axios.post("/api/reset-password", {
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
      console.log(err)

      setError(
        err.response?.data?.message ||
        "Errore durante il reset della password"
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="container mt-5">
      <div className="row justify-content-center">
        <div className="col-md-5">

          <div className="card shadow">
            <div className="card-body">

              <h3 className="text-center mb-3">
                Reimposta password 🔐
              </h3>

              <p className="text-muted text-center">
                Inserisci una nuova password per il tuo account
              </p>

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

                {error && (
                  <div className="alert alert-danger">
                    {error}
                  </div>
                )}

                {success && (
                  <div className="alert alert-success">
                    {success}
                  </div>
                )}

                <LoaderButton
                  type="submit"
                  loading={loading}
                  className="btn btn-primary w-100"
                >
                  Salva nuova password
                </LoaderButton>

                <div className="text-center mt-3">
                  <a href="/login">Torna al login</a>
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