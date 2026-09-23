import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import { useSEO } from "../../hooks/useSEO"
import api from "../../service/api"
import LoaderButton from "../../components/LoaderButton"
import { logError } from "../../utils/logger"
import styles from "./CreaEvento.module.css"

const EVENT_TYPES = [
  "Compleanno",
  "Serata tra amici",
  "Laurea",
  "Addio al nubilato/celibato",
  "Altro",
]

function CreaEvento() {
  const { user, token } = useAuth()
  const isLoggedIn = !!(user && token)

  useSEO({
    title: "Crea il Tuo Quiz Personalizzato per Eventi | Midalot",
    description:
      "Quiz 100% su misura per compleanni, addii al nubilato/celibato, lauree e serate tra amici. Preventivo gratuito e senza impegno: raccontaci la tua idea.",
  })

  const [form, setForm] = useState({
    name: "",
    contact: "",
    event_type: "",
    event_date: "",
    message: "",
  })
  const [loading, setLoading] = useState(false)
  const [success, setSuccess] = useState(false)
  const [error, setError] = useState("")

  useEffect(() => {
    if (!isLoggedIn) return

    setForm((prev) => ({
      ...prev,
      name: prev.name || user?.name || user?.nickname || "",
      contact: prev.contact || user?.email || "",
    }))
  }, [isLoggedIn, user])

  function handleChange(e) {
    const { name, value } = e.target
    setForm((prev) => ({ ...prev, [name]: value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setLoading(true)
    setError("")

    try {
      await api.post("/event-requests", form)
      setSuccess(true)
    } catch (err) {
      logError(err)
      setError(
        err.response?.data?.message ||
          "Errore durante l'invio della richiesta. Riprova tra poco o scrivici direttamente su Instagram."
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <section className={styles.page}>
      <div className={`container ${styles.wrap}`}>
        <Link to="/" className={styles.backLink}>
          <i className="bi bi-arrow-left"></i>
          Torna alla home
        </Link>

        <div className={styles.hero}>
          <span className={styles.eyebrow}>
            <i className="bi bi-stars"></i>
            Quiz su misura per il tuo evento
          </span>
          <h1>Crea il tuo evento con un quiz tutto tuo</h1>
          <p className={styles.heroText}>
            Vuoi rendere unica una festa di compleanno, un addio al nubilato/celibato, una laurea o una
            semplice serata tra amici? Midalot crea un quiz 100% personalizzato con domande dedicate ai
            festeggiati, temi su misura che farà divertire tutti gli ospiti!
          </p>
          <div className={styles.heroActions}>
            <a href="#richiedi-preventivo" className={styles.heroCta}>
              <i className="bi bi-send-fill"></i>
              Richiedi un preventivo gratuito
            </a>
            <a
              href="https://www.instagram.com/mida.lot/"
              target="_blank"
              rel="noopener noreferrer"
              className={styles.heroCtaSecondary}
            >
              <i className="bi bi-instagram"></i>
              Contattaci su Instagram @mida.lot
            </a>
          </div>
        </div>

        <div className={styles.section}>
          <h2>Perché scegliere un quiz personalizzato?</h2>
          <div className={styles.benefitsGrid}>
            <div className={styles.benefitCard}>
              <div className={styles.benefitIcon}>
                <i className="bi bi-palette-fill"></i>
              </div>
              <h3>Fatto su misura per te</h3>
              <p>
                Decidi tu i temi della serata (cinema, musica, serie TV, anni '90 ecc ecc) o inserisci
                domande divertenti basate su aneddoti e segreti dei tuoi amici.
              </p>
            </div>

            <div className={styles.benefitCard}>
              <div className={styles.benefitIcon}>
                <i className="bi bi-emoji-laughing-fill"></i>
              </div>
              <h3>Zero pensieri</h3>
              <p>Ci occupiamo noi della creazione e della logica del gioco. Tu devi solo pensare a divertirti.</p>
            </div>

            <div className={styles.benefitCard}>
              <div className={styles.benefitIcon}>
                <i className="bi bi-piggy-bank-fill"></i>
              </div>
              <h3>Prezzi accessibili a tutti</h3>
              <p>
                Soluzioni economiche e alla portata di qualsiasi budget, ideali per rendere speciale
                la tua festa senza spendere una fortuna.
              </p>
            </div>
          </div>
        </div>

        <div className={styles.section}>
          <h2>Come funziona?</h2>
          <div className={styles.stepsGrid}>
            <div className={styles.stepCard}>
              <span className={styles.stepNumber}>1</span>
              <h3>Raccontaci la tua idea</h3>
              <p>Compila il modulo qui sotto indicando la data, il tipo di evento e cosa vorresti nel quiz.</p>
            </div>

            <div className={styles.stepCard}>
              <span className={styles.stepNumber}>2</span>
              <h3>Ricevi il preventivo</h3>
              <p>Ti invieremo un preventivo gratuito e senza impegno con la nostra migliore offerta.</p>
            </div>

            <div className={styles.stepCard}>
              <span className={styles.stepNumber}>3</span>
              <h3>Gioca e divertiti</h3>
              <p>Prepariamo il quiz perfetto per te e sei pronto a sfidare i tuoi amici!</p>
            </div>
          </div>
        </div>

        <div id="richiedi-preventivo" className={styles.formSection}>
          <h2>Richiedi un preventivo gratuito</h2>
          <p className={styles.formIntro}>Compila i campi qui sotto e ti risponderemo nel più breve tempo possibile!</p>

          {success ? (
            <div className={styles.successBox}>
              <i className="bi bi-check-circle-fill"></i>
              <div>
                <strong>Richiesta inviata!</strong>
                <p className="mb-0">Ti risponderemo il prima possibile con un preventivo gratuito su misura per te.</p>
              </div>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className={styles.form}>
              {error && <div className="alert alert-danger">{error}</div>}

              <div className={styles.formRow}>
                <label className="form-label fw-bold" htmlFor="name">Nome e Cognome</label>
                <input
                  id="name"
                  name="name"
                  type="text"
                  className="form-control"
                  required
                  maxLength={255}
                  value={form.name}
                  onChange={handleChange}
                />
              </div>

              <div className={styles.formRow}>
                <label className="form-label fw-bold" htmlFor="contact">Email o Telefono</label>
                <input
                  id="contact"
                  name="contact"
                  type="text"
                  className="form-control"
                  required
                  maxLength={255}
                  placeholder="Come preferisci essere ricontattato/a"
                  value={form.contact}
                  onChange={handleChange}
                />
              </div>

              <div className={styles.formRow}>
                <label className="form-label fw-bold" htmlFor="event_type">Tipo di evento</label>
                <select
                  id="event_type"
                  name="event_type"
                  className="form-select"
                  value={form.event_type}
                  onChange={handleChange}
                >
                  <option value="">Seleziona (opzionale)</option>
                  {EVENT_TYPES.map((type) => (
                    <option key={type} value={type}>{type}</option>
                  ))}
                </select>
              </div>

              <div className={styles.formRow}>
                <label className="form-label fw-bold" htmlFor="event_date">Data prevista</label>
                <input
                  id="event_date"
                  name="event_date"
                  type="date"
                  className="form-control"
                  value={form.event_date}
                  onChange={handleChange}
                />
              </div>

              <div className={styles.formRowFull}>
                <label className="form-label fw-bold" htmlFor="message">Raccontaci la tua idea</label>
                <textarea
                  id="message"
                  name="message"
                  className="form-control"
                  rows={5}
                  required
                  minLength={5}
                  maxLength={3000}
                  placeholder="Indicaci quanti sarete, il tema desiderato o eventuali richieste particolari"
                  value={form.message}
                  onChange={handleChange}
                />
              </div>

              <LoaderButton type="submit" className={`btn ${styles.submitBtn}`} loading={loading}>
                <i className="bi bi-send-fill"></i>
                Invia la richiesta
              </LoaderButton>
            </form>
          )}
        </div>
      </div>
    </section>
  )
}

export default CreaEvento
