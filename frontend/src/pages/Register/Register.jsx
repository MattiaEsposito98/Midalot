import { useState } from "react"
import axios from "axios"
import { Link, useNavigate } from "react-router-dom"
import css from "./Register.module.css"
import LoaderButton from "../../components/LoaderButton"

function Register() {
  const navigate = useNavigate()

  const [form, setForm] = useState({
    name: "",
    nickname: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
    birth_date: "",
    city_id: ""
  })

  const [citySearch, setCitySearch] = useState("")
  const [cities, setCities] = useState([])
  const [loading, setLoading] = useState(false)
  const [errors, setErrors] = useState({})
  const [showPassword, setShowPassword] = useState(false)
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false)

  const nicknameRegex = /^(?!.*\.\.)(?!.*\.$)(?!^\.)[a-z0-9._]+$/

  const handleChange = (e) => {
    let value = e.target.value

    if (e.target.name === "nickname") {
      value = value.replace(/\s/g, "").toLowerCase()
    }

    if (e.target.name === "phone") {
      value = value.replace(/[^\d+\s]/g, "")
    }

    setForm((prev) => ({
      ...prev,
      [e.target.name]: value
    }))
  }

  const searchCities = async (value) => {
    setCitySearch(value)
    setForm((prev) => ({ ...prev, city_id: "" }))

    if (value.length < 2) {
      setCities([])
      return
    }

    try {
      const res = await axios.get("/cities/search?q=" + encodeURIComponent(value))
      setCities(res.data)
    } catch (err) {
      console.error(err)
    }
  }

  const selectCity = (city) => {
    setForm((prev) => ({
      ...prev,
      city_id: city.id
    }))

    setCitySearch(city.name)
    setCities([])
  }

  const validateForm = () => {
    const newErrors = {}

    if (!form.name.trim()) {
      newErrors.name = "Il nome e' obbligatorio"
    }

    if (!form.nickname.trim()) {
      newErrors.nickname = "Il nickname e' obbligatorio"
    } else if (form.nickname.length < 3 || form.nickname.length > 30) {
      newErrors.nickname = "Il nickname deve essere tra 3 e 30 caratteri"
    } else if (!nicknameRegex.test(form.nickname)) {
      newErrors.nickname = "Formato nickname non valido"
    }

    if (!form.email.trim()) {
      newErrors.email = "Email obbligatoria"
    }

    if (!form.password) {
      newErrors.password = "Password obbligatoria"
    }

    if (form.password !== form.password_confirmation) {
      newErrors.password_confirmation = "Le password non coincidono"
    }

    if (!form.birth_date) {
      newErrors.birth_date = "Data di nascita obbligatoria"
    }

    if (!form.city_id) {
      newErrors.city_id = "Seleziona una citta' valida"
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e) => {
    e.preventDefault()

    if (!validateForm()) return

    setLoading(true)
    setErrors({})

    try {
      await axios.post("/api/register", form)

      alert("Registrazione completata! Controlla la tua email, anche nella cartella spam, per verificare l'account.")
      navigate("/")
    } catch (err) {
      console.error(err)

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {})
      } else {
        alert("Si e' verificato un errore durante la registrazione.")
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className={`${css.registerPage} container`}>
      <div className="row justify-content-center">
        <div className="col-lg-8 col-md-10">
          <div className={`card ${css.registerCard}`}>
            <div className="card-body">
              <div className={css.header}>
                <span className={css.iconBadge}>
                  <i className="bi bi-person-plus-fill"></i>
                </span>
                <h1>Registrazione</h1>
                <p>Crea il tuo account per salvare progressi, storico e training.</p>
              </div>

              <form onSubmit={handleSubmit}>
                <div className="row">
                  <div className="col-md-6 mb-3">
                    <label className="form-label">Nome</label>
                    <input
                      className="form-control"
                      name="name"
                      value={form.name}
                      onChange={handleChange}
                      disabled={loading}
                      autoComplete="name"
                    />
                    {errors.name && <div className="text-danger small mt-1">{errors.name}</div>}
                  </div>

                  <div className="col-md-6 mb-3">
                    <label className="form-label">Nickname</label>
                    <input
                      className="form-control"
                      name="nickname"
                      value={form.nickname}
                      placeholder="es. mario.rossi_12"
                      onChange={handleChange}
                      disabled={loading}
                      autoComplete="username"
                    />
                    {errors.nickname && <div className="text-danger small mt-1">{errors.nickname}</div>}
                  </div>
                </div>

                <div className="row">
                  <div className="col-md-6 mb-3">
                    <label className="form-label">Email</label>
                    <input
                      className="form-control"
                      name="email"
                      type="email"
                      value={form.email}
                      onChange={handleChange}
                      disabled={loading}
                      autoComplete="email"
                    />
                    {errors.email && <div className="text-danger small mt-1">{errors.email}</div>}
                  </div>

                  <div className="col-md-6 mb-3">
                    <label className="form-label">Cellulare <span className="text-muted">(opzionale)</span></label>
                    <input
                      className="form-control"
                      name="phone"
                      type="tel"
                      value={form.phone}
                      placeholder="es. +39 333 1234567"
                      onChange={handleChange}
                      disabled={loading}
                      autoComplete="tel"
                    />
                    {errors.phone && <div className="text-danger small mt-1">{errors.phone}</div>}
                  </div>
                </div>

                <div className="row">
                  <div className="col-md-6 mb-3">
                    <label className="form-label">Data di nascita</label>
                    <input
                      type="date"
                      className="form-control"
                      name="birth_date"
                      value={form.birth_date}
                      onChange={handleChange}
                      disabled={loading}
                    />
                    {errors.birth_date && <div className="text-danger small mt-1">{errors.birth_date}</div>}
                  </div>

                  <div className={`col-md-6 mb-3 ${css.cityWrapper}`}>
                    <label className="form-label">Comune</label>
                    <input
                      className="form-control"
                      placeholder="Cerca comune"
                      value={citySearch}
                      onChange={(e) => searchCities(e.target.value)}
                      disabled={loading}
                      autoComplete="address-level2"
                    />

                    {cities.length > 0 && (
                      <div className={css.cityDropdown}>
                        {cities.map((city) => (
                          <button
                            type="button"
                            key={city.id}
                            className={css.cityItem}
                            onClick={() => selectCity(city)}
                          >
                            {city.name}
                          </button>
                        ))}
                      </div>
                    )}

                    {errors.city_id && <div className="text-danger small mt-1">{errors.city_id}</div>}
                  </div>
                </div>

                <div className="row">
                  <div className="col-md-6 mb-3">
                    <label className="form-label">Password</label>
                    <div className={css.passwordWrapper}>
                      <input
                        type={showPassword ? "text" : "password"}
                        className="form-control"
                        name="password"
                        value={form.password}
                        onChange={handleChange}
                        disabled={loading}
                        autoComplete="new-password"
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
                    {errors.password && <div className="text-danger small mt-1">{errors.password}</div>}
                  </div>

                  <div className="col-md-6 mb-3">
                    <label className="form-label">Conferma password</label>
                    <div className={css.passwordWrapper}>
                      <input
                        type={showPasswordConfirmation ? "text" : "password"}
                        className="form-control"
                        name="password_confirmation"
                        value={form.password_confirmation}
                        onChange={handleChange}
                        disabled={loading}
                        autoComplete="new-password"
                      />

                      <button
                        type="button"
                        className={css.passwordToggle}
                        onClick={() => setShowPasswordConfirmation((prev) => !prev)}
                        disabled={loading}
                        aria-label={showPasswordConfirmation ? "Nascondi conferma password" : "Mostra conferma password"}
                      >
                        <i className={`bi ${showPasswordConfirmation ? "bi-eye-slash" : "bi-eye"}`}></i>
                      </button>
                    </div>
                    {errors.password_confirmation && (
                      <div className="text-danger small mt-1">{errors.password_confirmation}</div>
                    )}
                  </div>
                </div>

                <LoaderButton
                  type="submit"
                  loading={loading}
                  className={`btn btn-primary w-100 mt-3 ${css.submitBtn}`}
                >
                  Registrati
                </LoaderButton>

                <div className={css.switchText}>
                  Hai gia' un account? <Link to="/login">Accedi</Link>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Register
