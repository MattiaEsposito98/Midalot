import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import { useSEO } from "../../hooks/useSEO"
import api from "../../service/api"
import LoaderButton from "../../components/LoaderButton"
import { logError } from "../../utils/logger"
import { trackEvent } from "../../utils/analytics"
import styles from "./ServiziDigitali.module.css"

const EMAIL = "midalot@libero.it"
const INSTAGRAM = "https://www.instagram.com/mida.lot/"

const SERVICES = [
  {
    key: "sito",
    icon: "bi-window-sidebar",
    tone: "blue",
    title: "Siti web vetrina",
    text: "Presenta la tua attività online con un sito moderno, professionale e pensato prima di tutto per lo smartphone.",
    points: ["Design su misura, niente template", "Pronto per essere trovato su Google", "Veloce anche da cellulare"],
  },
  {
    key: "ecommerce",
    icon: "bi-bag-check-fill",
    tone: "green",
    title: "E-commerce",
    text: "Vendi i tuoi prodotti online con un negozio digitale completo, personalizzato e facile da gestire.",
    points: ["Catalogo e ordini sotto controllo", "Pagamenti online sicuri", "Gestione semplice, anche da solo"],
  },
  {
    key: "gestionale",
    icon: "bi-kanban-fill",
    tone: "teal",
    title: "Gestionali aziendali",
    text: "Semplifica il lavoro di ogni giorno con un software fatto per te: clienti, ordini, documenti, appuntamenti, scadenze e attività.",
    points: ["Tutti i dati in un unico posto", "Promemoria e scadenze automatiche", "Accessi diversi per chi lavora con te"],
  },
  {
    key: "su-misura",
    icon: "bi-sliders",
    tone: "yellow",
    title: "Soluzioni su misura",
    text: "Non proponiamo soluzioni standard uguali per tutti: sviluppiamo strumenti in base alle reali esigenze della tua attività.",
    points: ["Partiamo da come lavori tu", "Solo le funzioni che ti servono", "Cresce insieme alla tua attività"],
  },
]

const SERVICE_OPTIONS = ["Sito web vetrina", "E-commerce", "Gestionale aziendale", "Soluzione su misura", "Non lo so ancora"]

// Cosa abbiamo costruito in Midalot: fatti verificabili da chiunque sul sito.
const SHOWCASE = [
  { icon: "bi-broadcast", title: "Quiz live in contemporanea", text: "Tutti i giocatori vedono la stessa domanda nello stesso istante, con classifica finale e premi automatici." },
  { icon: "bi-person-bounding-box", title: "Avatar 3D personalizzabile", text: "Ogni utente crea il suo personaggio, sblocca oggetti salendo di livello e li compra nel negozio interno." },
  { icon: "bi-trophy-fill", title: "Classifiche, livelli e premi", text: "Punteggi, esperienza, monete e badge calcolati dal server, con controlli contro chi prova a barare." },
  { icon: "bi-speedometer2", title: "Pannello di amministrazione", text: "Quiz, utenti, negozio, richieste e premi gestiti da un pannello costruito su misura." },
  { icon: "bi-phone", title: "Pensato per lo smartphone", text: "Ogni pagina funziona dal cellulare, dove gioca la maggior parte degli utenti." },
  { icon: "bi-google", title: "Trovabile su Google", text: "Titoli, descrizioni, mappa del sito e statistiche collegate a Search Console e Analytics." },
]

const STEPS = [
  { title: "Ci racconti il progetto", text: "Compili il modulo o ci scrivi: cosa fai, cosa ti serve, che idea hai in testa." },
  { title: "Ti proponiamo la soluzione", text: "Ti spieghiamo cosa realizzare, i tempi e il preventivo. Gratis e senza impegno." },
  { title: "Lo costruiamo insieme", text: "Ti mostriamo i passi avanti mentre lavoriamo, così il risultato è come lo volevi." },
  { title: "Online e seguito", text: "Lo pubblichiamo, ti insegniamo a usarlo e restiamo disponibili dopo la consegna." },
]

const FAQ = [
  {
    q: "Quanto costa un sito o un gestionale?",
    a: "Dipende da cosa ti serve: un sito vetrina è molto diverso da un gestionale con più utenti. Raccontaci il progetto e ti mandiamo un preventivo gratuito e senza impegno.",
  },
  {
    q: "Funzionerà bene dal cellulare?",
    a: "Sì. Progettiamo ogni pagina partendo dallo smartphone, perché è da lì che arriva la maggior parte delle visite.",
  },
  {
    q: "Potrò aggiornarlo da solo?",
    a: "Sì: dove serve prepariamo un pannello di gestione semplice per modificare testi, prodotti o dati senza conoscenze tecniche.",
  },
  {
    q: "Non so esattamente cosa mi serve. Posso contattarvi lo stesso?",
    a: "Certo. Spesso si parte da un problema (troppi fogli Excel, appuntamenti persi, nessuna presenza online): ci pensiamo insieme e ti proponiamo la soluzione più adatta.",
  },
]

function ServiziDigitali() {
  const { user, token } = useAuth()
  const isLoggedIn = !!(user && token)

  useSEO({
    title: "Siti Web, E-commerce e Gestionali su Misura | Midalot",
    description:
      "Realizziamo siti web vetrina, e-commerce e gestionali aziendali su misura, ottimizzati per smartphone e Google. Raccontaci il tuo progetto: preventivo gratuito.",
    jsonLd: {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Service",
          name: "Siti web, e-commerce e gestionali su misura",
          serviceType: "Sviluppo web e software gestionali",
          provider: { "@type": "Organization", name: "Midalot", url: "https://midalot.live", email: EMAIL },
          areaServed: { "@type": "Country", name: "Italia" },
          url: "https://midalot.live/servizi-digitali",
          hasOfferCatalog: {
            "@type": "OfferCatalog",
            name: "Servizi digitali",
            itemListElement: SERVICES.map((s) => ({ "@type": "Offer", itemOffered: { "@type": "Service", name: s.title, description: s.text } })),
          },
        },
        {
          "@type": "FAQPage",
          mainEntity: FAQ.map((f) => ({ "@type": "Question", name: f.q, acceptedAnswer: { "@type": "Answer", text: f.a } })),
        },
      ],
    },
  })

  const [form, setForm] = useState({ name: "", contact: "", event_type: "", message: "" })
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

  function chooseService(title) {
    setForm((prev) => ({ ...prev, event_type: title }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setLoading(true)
    setError("")

    try {
      await api.post("/event-requests", { ...form, kind: "servizi" })
      setSuccess(true)
      trackEvent("generate_lead", { lead_type: "servizi_digitali", service: form.event_type || "non_indicato" })
    } catch (err) {
      logError(err)
      setError(
        err.response?.data?.message ||
          "Errore durante l'invio. Riprova tra poco oppure scrivici via email o su Instagram."
      )
    } finally {
      setLoading(false)
    }
  }

  const contactClick = (method) => () => trackEvent("contact_click", { method, page: "servizi_digitali" })

  return (
    <section className={styles.page}>
      <div className={`container ${styles.wrap}`}>
        {/* ---------------------------------------------------------------- Apertura */}
        <header className={styles.hero}>
          <div className={styles.heroCopy}>
            <span className={styles.eyebrow}>
              <i className="bi bi-code-slash"></i>
              Servizi digitali
            </span>
            <h1>
              Siti web, e-commerce e gestionali <span className={styles.highlight}>su misura</span> per la tua attività
            </h1>
            <p className={styles.heroText}>
              Dalla semplice presenza online alla gestione completa del lavoro: realizziamo soluzioni digitali
              pensate per aiutare professionisti e attività a crescere, lavorare meglio e gestire tutto in modo più semplice.
            </p>
            <div className={styles.heroActions}>
              <a href="#contatti" className={styles.ctaPrimary}>
                <i className="bi bi-chat-dots-fill"></i>
                Parliamo del tuo progetto
              </a>
              <a href="#fatto-da-noi" className={styles.ctaGhost}>
                <i className="bi bi-eye-fill"></i>
                Guarda cosa abbiamo costruito
              </a>
            </div>
          </div>

          {/* Illustrazione: tre schermate stilizzate (solo forme, nessun contenuto finto). */}
          <div className={styles.heroArt} aria-hidden="true">
            <div className={`${styles.screen} ${styles.screenSite}`}>
              <div className={styles.screenBar}><span></span><span></span><span></span></div>
              <div className={styles.siteHero}></div>
              <div className={styles.lines}><i></i><i></i><i></i></div>
              <div className={styles.siteCards}><b></b><b></b><b></b></div>
            </div>
            <div className={`${styles.screen} ${styles.screenShop}`}>
              <div className={styles.screenBar}><span></span><span></span><span></span></div>
              <div className={styles.shopGrid}>
                <b><i className="bi bi-bag-fill"></i></b><b><i className="bi bi-watch"></i></b>
                <b><i className="bi bi-headphones"></i></b><b><i className="bi bi-backpack-fill"></i></b>
              </div>
            </div>
            <div className={`${styles.screen} ${styles.screenAdmin}`}>
              <div className={styles.screenBar}><span></span><span></span><span></span></div>
              <div className={styles.adminBody}>
                <div className={styles.adminSide}><i></i><i></i><i></i><i></i></div>
                <div className={styles.adminMain}>
                  <div className={styles.kpis}><b></b><b></b><b></b></div>
                  <div className={styles.chart}>
                    <span style={{ height: "35%" }}></span><span style={{ height: "55%" }}></span>
                    <span style={{ height: "45%" }}></span><span style={{ height: "70%" }}></span>
                    <span style={{ height: "60%" }}></span><span style={{ height: "88%" }}></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </header>

        <ul className={styles.trustStrip}>
          <li><i className="bi bi-phone-fill"></i> Pensati per lo smartphone</li>
          <li><i className="bi bi-search"></i> Pronti per Google</li>
          <li><i className="bi bi-person-gear"></i> Facili da gestire</li>
          <li><i className="bi bi-headset"></i> Seguiti anche dopo</li>
        </ul>

        {/* ---------------------------------------------------------------- Servizi */}
        <section className={styles.block} aria-labelledby="cosa-realizziamo">
          <div className={styles.blockHead}>
            <span className={styles.kicker}>Cosa realizziamo</span>
            <h2 id="cosa-realizziamo">Una soluzione per ogni fase della tua attività</h2>
          </div>

          <div className={styles.servicesGrid}>
            {SERVICES.map((s) => (
              <article key={s.key} className={`${styles.serviceCard} ${styles[`tone_${s.tone}`]}`}>
                <div className={styles.serviceIcon}>
                  <i className={`bi ${s.icon}`}></i>
                </div>
                <h3>{s.title}</h3>
                <p>{s.text}</p>
                <ul className={styles.checkList}>
                  {s.points.map((p) => (
                    <li key={p}><i className="bi bi-check-circle-fill"></i>{p}</li>
                  ))}
                </ul>
                <a
                  href="#contatti"
                  className={styles.serviceLink}
                  onClick={() => chooseService(SERVICE_OPTIONS[SERVICES.indexOf(s)])}
                >
                  Chiedi un preventivo <i className="bi bi-arrow-right"></i>
                </a>
              </article>
            ))}
          </div>
        </section>

        {/* ---------------------------------------------------------------- Caso studio */}
        <section id="fatto-da-noi" className={styles.showcase} aria-labelledby="fatto-da-noi-title">
          <div className={styles.showcaseIntro}>
            <span className={styles.kickerLight}>Il nostro biglietto da visita</span>
            <h2 id="fatto-da-noi-title">Midalot l'abbiamo costruito noi, da zero</h2>
            <p>
              Il sito che stai guardando è il nostro lavoro: una piattaforma completa con quiz in diretta,
              giocatori registrati, classifiche, un negozio interno e un pannello di gestione. Le stesse competenze
              le mettiamo nel tuo progetto.
            </p>
            <Link to="/" className={styles.ctaPrimary}>
              <i className="bi bi-joystick"></i>
              Esplora Midalot
            </Link>
          </div>

          <div className={styles.showcaseGrid}>
            {SHOWCASE.map((f) => (
              <div key={f.title} className={styles.feature}>
                <i className={`bi ${f.icon}`}></i>
                <div>
                  <h3>{f.title}</h3>
                  <p>{f.text}</p>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* ---------------------------------------------------------------- Come lavoriamo */}
        <section className={styles.block} aria-labelledby="come-lavoriamo">
          <div className={styles.blockHead}>
            <span className={styles.kicker}>Come lavoriamo</span>
            <h2 id="come-lavoriamo">Dall'idea al progetto online, in 4 passi</h2>
          </div>
          <ol className={styles.steps}>
            {STEPS.map((step, i) => (
              <li key={step.title} className={styles.step}>
                <span className={styles.stepNumber}>{i + 1}</span>
                <h3>{step.title}</h3>
                <p>{step.text}</p>
              </li>
            ))}
          </ol>
        </section>

        {/* ---------------------------------------------------------------- Domande */}
        <section className={styles.block} aria-labelledby="domande">
          <div className={styles.blockHead}>
            <span className={styles.kicker}>Domande frequenti</span>
            <h2 id="domande">Le risposte prima di scriverci</h2>
          </div>
          <div className={styles.faq}>
            {FAQ.map((f) => (
              <details key={f.q} className={styles.faqItem}>
                <summary>
                  {f.q}
                  <i className="bi bi-plus-lg"></i>
                </summary>
                <p>{f.a}</p>
              </details>
            ))}
          </div>
        </section>

        {/* ---------------------------------------------------------------- Contatti */}
        <section id="contatti" className={styles.contact} aria-labelledby="contatti-title">
          <div className={styles.contactSide}>
            <span className={styles.kickerLight}>Contatti</span>
            <h2 id="contatti-title">Hai un progetto in mente o un'attività da digitalizzare?</h2>
            <p>Scrivici: ne parliamo insieme e troviamo la soluzione più adatta alle tue esigenze. Il preventivo è gratuito e senza impegno.</p>
            <div className={styles.contactLinks}>
              <a href={`mailto:${EMAIL}?subject=${encodeURIComponent("Richiesta servizi digitali")}`} onClick={contactClick("email")}>
                <i className="bi bi-envelope-fill"></i>
                <span><small>Email</small>{EMAIL}</span>
              </a>
              <a href={INSTAGRAM} target="_blank" rel="noopener noreferrer" onClick={contactClick("instagram")}>
                <i className="bi bi-instagram"></i>
                <span><small>Instagram</small>@mida.lot</span>
              </a>
            </div>
          </div>

          <div className={styles.formCard}>
            {success ? (
              <div className={styles.successBox} role="status">
                <i className="bi bi-check-circle-fill"></i>
                <div>
                  <strong>Richiesta inviata!</strong>
                  <p className="mb-0">Grazie, ti ricontatteremo il prima possibile per parlare del tuo progetto.</p>
                </div>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className={styles.form}>
                <h3>Raccontaci il tuo progetto</h3>
                {error && <div className="alert alert-danger">{error}</div>}

                <div>
                  <label className="form-label fw-bold" htmlFor="sd-name">Nome e cognome</label>
                  <input id="sd-name" name="name" type="text" className="form-control" required maxLength={255}
                    autoComplete="name" value={form.name} onChange={handleChange} />
                </div>

                <div>
                  <label className="form-label fw-bold" htmlFor="sd-contact">Email o telefono</label>
                  <input id="sd-contact" name="contact" type="text" className="form-control" required maxLength={255}
                    placeholder="Come preferisci essere ricontattato/a" value={form.contact} onChange={handleChange} />
                </div>

                <fieldset className={styles.serviceChoice}>
                  <legend className="form-label fw-bold">Di cosa hai bisogno?</legend>
                  <div className={styles.chips}>
                    {SERVICE_OPTIONS.map((opt) => (
                      <label key={opt} className={`${styles.chip} ${form.event_type === opt ? styles.chipActive : ""}`}>
                        <input type="radio" name="event_type" value={opt} checked={form.event_type === opt} onChange={handleChange} />
                        {opt}
                      </label>
                    ))}
                  </div>
                </fieldset>

                <div>
                  <label className="form-label fw-bold" htmlFor="sd-message">Il tuo progetto</label>
                  <textarea id="sd-message" name="message" className="form-control" rows={5} required minLength={5} maxLength={3000}
                    placeholder="Di cosa si occupa la tua attività e cosa vorresti realizzare?"
                    value={form.message} onChange={handleChange} />
                </div>

                <LoaderButton type="submit" className={`btn ${styles.submitBtn}`} loading={loading}>
                  <i className="bi bi-send-fill"></i>
                  Invia la richiesta
                </LoaderButton>
                <p className={styles.privacyNote}>
                  Useremo i tuoi dati solo per risponderti. <Link to="/privacy">Privacy</Link>
                </p>
              </form>
            )}
          </div>
        </section>

        <p className={styles.signature}>La tua attività, le nostre soluzioni digitali.</p>

        <aside className={styles.crossLink}>
          <i className="bi bi-stars"></i>
          <div>
            <strong>Cerchi un quiz personalizzato per una festa?</strong>
            <span>Compleanni, lauree, addii al nubilato: lo creiamo noi su misura.</span>
          </div>
          <Link to="/crea-il-tuo-evento">Crea il tuo evento <i className="bi bi-arrow-right"></i></Link>
        </aside>
      </div>
    </section>
  )
}

export default ServiziDigitali
