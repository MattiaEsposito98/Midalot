import { Link } from "react-router-dom"
import styles from "./LegalPage.module.css"

const CONTACT_EMAIL = "midalot@libero.it"

const pages = {
  privacy: {
    eyebrow: "Informativa privacy",
    title: "Privacy Policy",
    updated: "Ultimo aggiornamento: 29 maggio 2026",
    intro:
      "Questa informativa descrive come Midalot tratta i dati personali degli utenti che usano il sito, l'area training e i quiz assegnati.",
    sections: [
      {
        title: "Titolare del trattamento",
        body: [
          "Il progetto Midalot e' gestito da Antonio Scalamogna e Mattia Esposito, titolari del trattamento dei dati raccolti tramite il sito.",
          "Per richieste privacy puoi scrivere a " + CONTACT_EMAIL + "."
        ],
      },
      {
        title: "Dati trattati",
        body: [
          "Dati account: nome, nickname, email, password cifrata, eventuale telefono, data di nascita e citta'.",
          "Dati di utilizzo: quiz assegnati, risposte, punteggi, tempi, classifiche, sessioni training degli utenti registrati.",
          "Dati tecnici: indirizzo IP, user agent, log di accesso e informazioni necessarie alla sicurezza del servizio.",
          "Gli ospiti possono usare i training pubblici: i risultati degli ospiti non vengono salvati nei progressi o nelle classifiche."
        ],
      },
      {
        title: "Finalita'",
        body: [
          "Creare e gestire l'account utente.",
          "Consentire lo svolgimento dei quiz assegnati e dei training.",
          "Mostrare progressi, storico e classifiche quando disponibili.",
          "Gestire sicurezza, prevenzione abusi, verifica email, reset password e assistenza."
        ],
      },
      {
        title: "Base giuridica",
        body: [
          "Esecuzione del servizio richiesto dall'utente per account, quiz, training e profilo.",
          "Obblighi legali, quando applicabili.",
          "Legittimo interesse alla sicurezza del servizio, prevenzione abusi e tutela dell'infrastruttura.",
          "Consenso solo per eventuali finalita' facoltative future, come newsletter o cookie non tecnici."
        ],
      },
      {
        title: "Conservazione",
        body: [
          "I dati account sono conservati finche' l'account resta attivo o finche' servono per obblighi legali o tutela dei diritti.",
          "Risultati, storico quiz e training sono conservati per fornire progressi e classifiche.",
          "I log tecnici e di sicurezza sono conservati per un massimo di 12 mesi, salvo necessita' diverse legate alla sicurezza o a obblighi di legge."
        ],
      },
      {
        title: "Diritti degli utenti",
        body: [
          "Gli utenti possono chiedere accesso, rettifica, cancellazione, limitazione, opposizione e portabilita' nei casi previsti dal GDPR.",
          "Le richieste possono essere inviate a " + CONTACT_EMAIL + ".",
          "L'utente puo' inoltre proporre reclamo al Garante per la protezione dei dati personali."
        ],
      },
      {
        title: "Sicurezza",
        body: [
          "Le password sono memorizzate con hashing sicuro e non sono leggibili dagli amministratori.",
          "L'accesso backend e' riservato agli amministratori.",
          "Le API protette richiedono autenticazione e verifica email."
        ],
      },
    ],
  },
  terms: {
    eyebrow: "Regole del servizio",
    title: "Termini e Condizioni",
    updated: "Ultimo aggiornamento: 29 maggio 2026",
    intro:
      "Questi termini regolano l'utilizzo di Midalot, dei quiz assegnati, del training pubblico e delle classifiche.",
    sections: [
      {
        title: "Uso del servizio",
        body: [
          "Midalot permette agli utenti registrati di svolgere quiz assegnati, usare training e consultare progressi e classifiche.",
          "Gli ospiti possono usare i training pubblici, ma i risultati non vengono salvati."
        ],
      },
      {
        title: "Account",
        body: [
          "L'utente deve fornire dati corretti e mantenere riservate le credenziali.",
          "Non e' consentito usare account di altri utenti o tentare accessi non autorizzati.",
          "L'account puo' richiedere verifica email prima dell'utilizzo completo del servizio."
        ],
      },
      {
        title: "Quiz e classifiche",
        body: [
          "I quiz assegnati sono disponibili solo agli utenti autorizzati.",
          "Le classifiche possono mostrare il nickname, il punteggio, le risposte corrette e il tempo totale.",
          "Comportamenti fraudolenti, manipolazione delle richieste o abuso del sistema possono comportare sospensione o rimozione dei risultati."
        ],
      },
      {
        title: "Contenuti e disponibilita'",
        body: [
          "Midalot puo' modificare categorie, quiz, domande e funzionalita' per migliorare il servizio.",
          "Il servizio puo' essere temporaneamente non disponibile per manutenzione o problemi tecnici."
        ],
      },
      {
        title: "Limitazioni",
        body: [
          "E' vietato tentare di superare le protezioni, effettuare scraping aggressivo, inviare richieste automatizzate abusive o interferire con il funzionamento del servizio.",
          "I contenuti dei quiz non devono essere copiati, diffusi o usati fuori dal servizio senza autorizzazione."
        ],
      },
      {
        title: "Contatti",
        body: [
          "Midalot e' gestito da Mattia Esposito e Antonio Scalamogna. Per richieste relative al servizio puoi scrivere a " + CONTACT_EMAIL + "."
        ],
      },
    ],
  },
  cookies: {
    eyebrow: "Informativa cookie",
    title: "Cookie Policy",
    updated: "Ultimo aggiornamento: 29 maggio 2026",
    intro:
      "Questa pagina descrive l'uso di cookie e tecnologie simili su Midalot.",
    sections: [
      {
        title: "Cookie tecnici",
        body: [
          "Il backend Laravel puo' usare cookie tecnici necessari per l'area amministrativa, la sicurezza e la gestione della sessione.",
          "Questi cookie sono necessari al funzionamento del servizio e non richiedono consenso preventivo."
        ],
      },
      {
        title: "Local storage",
        body: [
          "Il frontend React puo' salvare localmente informazioni di accesso dell'utente, come token e dati profilo, per mantenere la sessione attiva.",
          "Questi dati restano nel browser dell'utente finche' non effettua logout o cancella i dati del browser."
        ],
      },
      {
        title: "Cookie analitici o marketing",
        body: [
          "Al momento Midalot non usa cookie di profilazione, advertising o analytics di terze parti.",
          "Se in futuro verranno aggiunti strumenti di analytics, pixel o marketing, questa pagina sara' aggiornata e, ove necessario, verra' richiesto consenso esplicito."
        ],
      },
      {
        title: "Gestione dal browser",
        body: [
          "L'utente puo' cancellare cookie e dati locali dalle impostazioni del proprio browser.",
          "La rimozione dei dati tecnici puo' richiedere un nuovo login o limitare alcune funzionalita'."
        ],
      },
    ],
  },
}

function LegalPage({ type }) {
  const page = pages[type]

  return (
    <section className={styles.page}>
      <div className={`container ${styles.wrap}`}>
        <Link to="/" className={styles.backLink}>
          <i className="bi bi-arrow-left"></i>
          Torna alla home
        </Link>

        <div className={styles.header}>
          <span className={styles.eyebrow}>{page.eyebrow}</span>
          <h1>{page.title}</h1>
          <p>{page.intro}</p>
          <small>{page.updated}</small>
        </div>

        <div className={styles.content}>
          {page.sections.map((section) => (
            <article className={styles.section} key={section.title}>
              <h2>{section.title}</h2>
              {section.body.map((text) => (
                <p key={text}>{text}</p>
              ))}
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}

export default LegalPage
