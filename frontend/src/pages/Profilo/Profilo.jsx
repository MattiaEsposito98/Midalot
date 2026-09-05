import { useEffect, useState } from "react"
import axios from "axios"
import { Link } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "./Profilo.module.css"
import { logError } from "../../utils/logger"
import { API_BASE } from "../../service/api"
import UserBadge from "../../components/UserBadge/UserBadge"

function Profilo() {
  const { token, user } = useAuth()

  const [stats, setStats] = useState({
    total: 0,
    active: 0,
    completed: 0,
    expired: 0,
  })

  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(false)
  const [saving, setSaving] = useState(false)
  const [errors, setErrors] = useState({})

  const [profile, setProfile] = useState({
    name: "",
    phone: "",
    birth_date: "",
    city_id: "",
  })

  const [citySearch, setCitySearch] = useState("")
  const [cities, setCities] = useState([])

  useEffect(() => {
    if (!user) return

    setProfile({
      name: user.name || user.nome || "",
      phone: user.phone || "",
      birth_date: user.birth_date ? String(user.birth_date).slice(0, 10) : "",
      city_id: user.city_id || "",
    })

    setCitySearch(user.city?.name || user.city_name || "")
  }, [user])

  useEffect(() => {
    async function loadStats() {
      try {
        const res = await fetch(`${API_BASE}/api/my-quizzes`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })

        const data = await res.json()
        const quizzes = data.quizzes || []

        const total = quizzes.length
        const active = quizzes.filter(
          (q) => q.status === "available" || q.status === "in_progress"
        ).length
        const completed = quizzes.filter((q) => q.status === "completed").length
        const expired = quizzes.filter((q) => q.status === "expired").length

        setStats({
          total,
          active,
          completed,
          expired,
        })
      } catch (error) {
        logError("Errore caricamento statistiche profilo", error)
      } finally {
        setLoading(false)
      }
    }

    loadStats()
  }, [token])

  const formatDate = (date) => {
    if (!date) return "-"

    return new Date(date).toLocaleDateString("it-IT", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    })
  }

  const handleProfileChange = (e) => {
    let value = e.target.value

    if (e.target.name === "phone") {
      value = value.replace(/[^\d+\s]/g, "")
    }

    setProfile((prev) => ({
      ...prev,
      [e.target.name]: value,
    }))
  }

  const searchCities = async (value) => {
    setCitySearch(value)

    setProfile((prev) => ({
      ...prev,
      city_id: "",
    }))

    if (value.length < 2) {
      setCities([])
      return
    }

    try {
      const res = await axios.get(`${API_BASE}/cities/search?q=` + encodeURIComponent(value))

      setCities(res.data)
    } catch (error) {
      logError("Errore ricerca comuni", error)
      setCities([])
    }
  }

  const selectCity = (city) => {
    setProfile((prev) => ({
      ...prev,
      city_id: city.id,
    }))

    setCitySearch(city.name)
    setCities([])
  }

  const cancelEdit = () => {
    setEditing(false)
    setErrors({})

    setProfile({
      name: user?.name || user?.nome || "",
      phone: user?.phone || "",
      birth_date: user?.birth_date ? String(user.birth_date).slice(0, 10) : "",
      city_id: user?.city_id || "",
    })

    setCitySearch(user?.city?.name || user?.city_name || "")
    setCities([])
  }

  const saveProfile = async () => {
    setSaving(true)
    setErrors({})

    try {
      const res = await fetch(`${API_BASE}/api/user/profile`, {
        method: "PUT",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(profile),
      })

      const data = await res.json()

      if (!res.ok) {
        if (res.status === 422) {
          setErrors(data.errors || {})
        } else {
          alert(data.message || "Errore durante il salvataggio del profilo.")
        }

        return
      }

      alert("Profilo aggiornato correttamente.")
      setEditing(false)

      /*
        Nota:
        Se nel tuo AuthContext hai una funzione per aggiornare l'utente,
        qui sarebbe meglio aggiornare anche user nel context.
        Esempio:
        setUser(data.user)
      */
    } catch (error) {
      logError("Errore salvataggio profilo", error)
      alert("Si è verificato un errore durante il salvataggio.")
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <h1 className={styles.title}>Il mio profilo</h1>
        <p className={styles.subtitle}>
          Qui puoi consultare i tuoi dati e un riepilogo della tua attività.
        </p>
      </div>

      <div className="row g-4">
        <div className="col-12 col-lg-6">
          <div className={styles.card}>
            <div className={styles.cardHeader}>
              <h2 className={styles.cardTitle}>Informazioni personali</h2>

              {!editing ? (
                <button
                  type="button"
                  className="btn btn-outline-primary btn-sm"
                  onClick={() => setEditing(true)}
                >
                  Modifica
                </button>
              ) : (
                <div className={styles.editActions}>
                  <button
                    type="button"
                    className="btn btn-outline-secondary btn-sm"
                    onClick={cancelEdit}
                    disabled={saving}
                  >
                    Annulla
                  </button>

                  <button
                    type="button"
                    className="btn btn-primary btn-sm"
                    onClick={saveProfile}
                    disabled={saving}
                  >
                    {saving ? "Salvataggio..." : "Salva"}
                  </button>
                </div>
              )}
            </div>

            <div className={styles.infoList}>
              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Nome</span>

                {!editing ? (
                  <strong className={styles.infoValue}>
                    {profile.name || "-"}
                  </strong>
                ) : (
                  <div className={styles.inputWrap}>
                    <input
                      className="form-control"
                      name="name"
                      value={profile.name}
                      onChange={handleProfileChange}
                      disabled={saving}
                      autoComplete="name"
                    />
                    {errors.name && (
                      <div className="text-danger small mt-1">{errors.name}</div>
                    )}
                  </div>
                )}
              </div>

              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Nickname</span>
                <strong className={styles.infoValue}>
                  {user?.nickname || "-"} <UserBadge label={user?.latest_monthly_badge?.label} />
                </strong>
              </div>

              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Email</span>
                <strong className={styles.infoValue}>
                  {user?.email || "-"}
                </strong>
              </div>

              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Cellulare</span>

                {!editing ? (
                  <strong className={styles.infoValue}>
                    {profile.phone || "-"}
                  </strong>
                ) : (
                  <div className={styles.inputWrap}>
                    <input
                      className="form-control"
                      name="phone"
                      type="tel"
                      value={profile.phone}
                      placeholder="es. +39 333 1234567"
                      onChange={handleProfileChange}
                      disabled={saving}
                      autoComplete="tel"
                    />
                    {errors.phone && (
                      <div className="text-danger small mt-1">{errors.phone}</div>
                    )}
                  </div>
                )}
              </div>

              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Data di nascita</span>

                {!editing ? (
                  <strong className={styles.infoValue}>
                    {formatDate(profile.birth_date)}
                  </strong>
                ) : (
                  <div className={styles.inputWrap}>
                    <input
                      className="form-control"
                      name="birth_date"
                      type="date"
                      value={profile.birth_date}
                      onChange={handleProfileChange}
                      disabled={saving}
                    />
                    {errors.birth_date && (
                      <div className="text-danger small mt-1">{errors.birth_date}</div>
                    )}
                  </div>
                )}
              </div>

              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Comune</span>

                {!editing ? (
                  <strong className={styles.infoValue}>
                    {citySearch || "-"}
                  </strong>
                ) : (
                  <div className={`${styles.inputWrap} ${styles.cityWrapper}`}>
                    <input
                      className="form-control"
                      placeholder="Cerca comune"
                      value={citySearch}
                      onChange={(e) => searchCities(e.target.value)}
                      disabled={saving}
                      autoComplete="address-level2"
                    />

                    {cities.length > 0 && (
                      <div className={styles.cityDropdown}>
                        {cities.map((city) => (
                          <div
                            key={city.id}
                            className={styles.cityItem}
                            onClick={() => selectCity(city)}
                          >
                            {city.name}
                          </div>
                        ))}
                      </div>
                    )}

                    {errors.city_id && (
                      <div className="text-danger small mt-1">{errors.city_id}</div>
                    )}

                    {editing && citySearch && !profile.city_id && !errors.city_id && (
                      <div className="text-danger small mt-1">
                        Seleziona un comune dal menu.
                      </div>
                    )}
                  </div>
                )}
              </div>

              <div className={styles.infoRow}>
                <span className={styles.infoLabel}>Ruolo</span>
                <strong className={styles.infoValue}>
                  {user?.is_admin ? "Admin" : "Utente"}
                </strong>
              </div>
            </div>
          </div>
        </div>

        <div className="col-12 col-lg-6">
          <div className={styles.card}>
            <h2 className={styles.cardTitle}>Statistiche quiz</h2>

            {loading ? (
              <div className={styles.loadingBox}>
                <div className="spinner-border text-primary" role="status"></div>
                <p className={styles.loadingText}>Caricamento statistiche...</p>
              </div>
            ) : (
              <div className={styles.statsGrid}>
                <div className={styles.statItem}>
                  <span className={styles.statLabel}>Totale assegnati</span>
                  <strong className={styles.statValue}>{stats.total}</strong>
                </div>

                <div className={styles.statItem}>
                  <span className={styles.statLabel}>Quiz attivi</span>
                  <strong className={styles.statValue}>{stats.active}</strong>
                </div>

                <div className={styles.statItem}>
                  <span className={styles.statLabel}>Completati</span>
                  <strong className={styles.statValue}>{stats.completed}</strong>
                </div>

                <div className={styles.statItem}>
                  <span className={styles.statLabel}>Scaduti</span>
                  <strong className={styles.statValue}>{stats.expired}</strong>
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="col-12">
          <div className={styles.card}>
            <div className={styles.securityWrap}>
              <div>
                <h2 className={styles.cardTitle}>Sicurezza</h2>
                <p className={styles.securityText}>
                  Se vuoi cambiare la tua password, clicca sul pulsante. Assicurati di scegliere una password sicura e unica.
                </p>
              </div>

              <Link to="/cambia-password" className={`btn btn-primary ${styles.passwordBtn}`}>
                Cambia password
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Profilo
