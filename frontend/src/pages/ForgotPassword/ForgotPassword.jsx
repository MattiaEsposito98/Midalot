import { useState } from "react"
import axios from "axios"
import { Link } from "react-router-dom"
import LoaderButton from "../../components/LoaderButton"

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
      console.log("ERRORE FORGOT PASSWORD:", err)
      setError("Errore del server. Riprova più tardi.")
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
              <h3 className="mb-3 text-center">Password dimenticata</h3>

              <p className="text-muted text-center">
                Inserisci la tua email e ti invieremo un link per reimpostare la password.
              </p>

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
                  Invia link di reset
                </LoaderButton>

                <div className="text-center mt-3">
                  <Link to="/">Torna al login</Link>
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