import { useState } from "react"
import { useAuth } from "../../context/AuthContext"
import styles from "./CambiaPassword.module.css"

function CambiaPassword() {
  const { token } = useAuth()

  const [form, setForm] = useState({
    current_password: "",
    password: "",
    password_confirmation: "",
  })

  const [showPasswords, setShowPasswords] = useState({
    current: false,
    new: false,
    confirm: false,
  })

  const [loading, setLoading] = useState(false)
  const [success, setSuccess] = useState("")
  const [error, setError] = useState("")

  const handleChange = (e) => {
    setForm({
      ...form,
      [e.target.name]: e.target.value,
    })
  }

  const togglePassword = (field) => {
    setShowPasswords((prev) => ({
      ...prev,
      [field]: !prev[field],
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError("")
    setSuccess("")

    if (!form.current_password.trim()) {
      setError("Inserisci la password attuale.")
      return
    }

    if (!form.password.trim()) {
      setError("Inserisci la nuova password.")
      return
    }

    if (form.password.length < 8) {
      setError("La nuova password deve contenere almeno 8 caratteri.")
      return
    }

    if (form.password !== form.password_confirmation) {
      setError("La conferma password non coincide.")
      return
    }

    try {
      setLoading(true)

      const res = await fetch("http://localhost:8000/api/change-password", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify(form),
      })

      const data = await res.json()

      if (!res.ok) {
        throw new Error(data.message || "Errore durante il cambio password.")
      }

      setSuccess("Password aggiornata con successo.")
      setForm({
        current_password: "",
        password: "",
        password_confirmation: "",
      })
    } catch (err) {
      setError(err.message || "Si è verificato un errore.")
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <h1 className={styles.title}>Cambia password</h1>
        <p className={styles.subtitle}>
          Aggiorna la tua password per mantenere il tuo account al sicuro.
        </p>
      </div>

      <div className="row justify-content-center">
        <div className="col-12 col-lg-8 col-xl-6">
          <div className={styles.card}>
            {success && (
              <div className={`alert alert-success ${styles.alertBox}`}>
                {success}
              </div>
            )}

            {error && (
              <div className={`alert alert-danger ${styles.alertBox}`}>
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div className={styles.formGroup}>
                <label className={styles.label} htmlFor="current_password">
                  Password attuale
                </label>
                <div className={styles.inputWrap}>
                  <input
                    id="current_password"
                    name="current_password"
                    type={showPasswords.current ? "text" : "password"}
                    className={`form-control ${styles.input}`}
                    value={form.current_password}
                    onChange={handleChange}
                    placeholder="Inserisci la password attuale"
                  />
                  <button
                    type="button"
                    className={styles.eyeBtn}
                    onClick={() => togglePassword("current")}
                  >
                    {showPasswords.current ? "Nascondi" : "Mostra"}
                  </button>
                </div>
              </div>

              <div className={styles.formGroup}>
                <label className={styles.label} htmlFor="password">
                  Nuova password
                </label>
                <div className={styles.inputWrap}>
                  <input
                    id="password"
                    name="password"
                    type={showPasswords.new ? "text" : "password"}
                    className={`form-control ${styles.input}`}
                    value={form.password}
                    onChange={handleChange}
                    placeholder="Inserisci la nuova password"
                  />
                  <button
                    type="button"
                    className={styles.eyeBtn}
                    onClick={() => togglePassword("new")}
                  >
                    {showPasswords.new ? "Nascondi" : "Mostra"}
                  </button>
                </div>
              </div>

              <div className={styles.formGroup}>
                <label className={styles.label} htmlFor="password_confirmation">
                  Conferma nuova password
                </label>
                <div className={styles.inputWrap}>
                  <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type={showPasswords.confirm ? "text" : "password"}
                    className={`form-control ${styles.input}`}
                    value={form.password_confirmation}
                    onChange={handleChange}
                    placeholder="Conferma la nuova password"
                  />
                  <button
                    type="button"
                    className={styles.eyeBtn}
                    onClick={() => togglePassword("confirm")}
                  >
                    {showPasswords.confirm ? "Nascondi" : "Mostra"}
                  </button>
                </div>
              </div>

              <div className={styles.infoBox}>
                La password deve contenere almeno 8 caratteri.
              </div>

              <button
                type="submit"
                className={`btn btn-primary w-100 ${styles.submitBtn}`}
                disabled={loading}
              >
                {loading ? "Aggiornamento..." : "Aggiorna password"}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  )
}

export default CambiaPassword