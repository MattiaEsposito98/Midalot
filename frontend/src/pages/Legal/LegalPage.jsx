import { Link } from "react-router-dom"
import { useCookieConsent } from "../../hooks/useCookieConsent"
import styles from "./LegalPage.module.css"

const CONTACT_EMAIL = "midalot@libero.it"

const pages = {
  privacy: {
    eyebrow: "Informativa privacy",
    title: "Privacy Policy",
    updated: "Ultimo aggiornamento: 26 agosto 2026",
    intro:
      "Questa informativa descrive come Midalot tratta i dati personali degli utenti che usano il sito e le modalita' di gioco Quiz One Shot, Midalario e Training.",
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
          "Dati di utilizzo: partecipazione a Quiz One Shot, Midalario e Training, risposte, punteggi, tempi, classifiche degli utenti registrati.",
          "Dati tecnici: indirizzo IP, user agent, log di accesso e informazioni necessarie alla sicurezza del servizio.",
          "Gli ospiti possono usare i training pubblici: i risultati degli ospiti non vengono salvati nei progressi o nelle classifiche."
        ],
      },
      {
        title: "Minori ed eta' minima",
        body: [
          "La registrazione e' consentita esclusivamente agli utenti che abbiano compiuto almeno 14 (quattordici) anni di eta', in linea con la soglia prevista dall'art. 2-quinquies del Codice Privacy italiano per la prestazione autonoma del consenso al trattamento dei dati personali da parte dei minori.",
          "Gli utenti di eta' compresa tra i 14 e i 17 anni possono registrarsi e prestare autonomamente il consenso al trattamento dei propri dati, dichiarando contestualmente di disporre dell'autorizzazione dei genitori o di chi ne esercita la responsabilita' genitoriale per la partecipazione al gioco, cosi' come previsto dal Regolamento di gioco.",
          "La piattaforma non e' destinata a utenti di eta' inferiore a 14 anni: la registrazione da parte di minori di 14 anni non e' consentita e, se rilevata, comporta la sospensione o cancellazione dell'account secondo quanto previsto dal Regolamento.",
          "In caso di vincita di un premio da parte di un utente minorenne, la piattaforma si riserva di richiedere conferma ed autorizzazione formale del genitore o tutore prima dell'erogazione, come previsto dal Regolamento."
        ],
      },
      {
        title: "Finalita'",
        body: [
          "Creare e gestire l'account utente.",
          "Consentire lo svolgimento di Quiz One Shot, Midalario e Training.",
          "Mostrare progressi, storico e classifiche quando disponibili.",
          "Gestire l'assegnazione e la consegna di eventuali premi previsti dal Regolamento di gioco, incluse le comunicazioni via email necessarie a tal fine.",
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
    updated: "Ultimo aggiornamento: 26 agosto 2026",
    intro:
      "Questi termini regolano l'utilizzo di Midalot e delle sue modalita' di gioco: Quiz One Shot, Midalario e Training.",
    sections: [
      {
        title: "Regolamento di gioco",
        body: [
          "Le regole specifiche di partecipazione, lo svolgimento delle diverse modalita' di gioco, il calcolo dei punteggi, le classifiche e l'assegnazione dei premi sono disciplinati dal " +
            "Regolamento ufficiale di gioco, che l'utente accetta espressamente in fase di registrazione insieme ai presenti Termini.",
          "In caso di contrasto tra questi Termini e il Regolamento su aspetti di gioco, punteggi o premi, prevale quanto stabilito dal Regolamento."
        ],
      },
      {
        title: "Uso del servizio",
        body: [
          "Midalot permette agli utenti registrati di svolgere i Quiz One Shot (disponibili per tutti gli utenti registrati, salvo eventuali quiz riservati a utenti specifici), di partecipare alle sessioni Midalario, di usare il Training e di consultare progressi e classifiche.",
          "Gli ospiti possono usare i training pubblici, ma i risultati non vengono salvati e non possono accedere ai Quiz One Shot ne' al Midalario."
        ],
      },
      {
        title: "Account",
        body: [
          "La registrazione e' riservata agli utenti che abbiano compiuto almeno 14 anni di eta'; gli utenti tra i 14 e i 17 anni devono disporre dell'autorizzazione dei genitori o di chi ne esercita la responsabilita' genitoriale, come previsto dal Regolamento e dalla Privacy Policy.",
          "L'utente deve fornire dati corretti e mantenere riservate le credenziali.",
          "Ciascuna persona fisica puo' registrare un solo account; non e' consentito usare account di altri utenti o tentare accessi non autorizzati.",
          "L'account puo' richiedere verifica email prima dell'utilizzo completo del servizio."
        ],
      },
      {
        title: "Quiz e classifiche",
        body: [
          "I Quiz One Shot sono in generale disponibili a tutti gli utenti registrati; alcuni quiz possono essere riservati dagli amministratori a utenti specifici.",
          "Le classifiche possono mostrare il nickname, il punteggio, le risposte corrette e il tempo totale, e possono essere diffuse sui canali social ufficiali secondo quanto previsto dal Regolamento.",
          "Comportamenti fraudolenti, manipolazione delle richieste o abuso del sistema possono comportare sospensione o rimozione dei risultati, come previsto anche dal Regolamento."
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
    updated: "Ultimo aggiornamento: 19 settembre 2026",
    intro:
      "Questa pagina descrive l'uso di cookie e tecnologie simili su Midalot.",
    sections: [
      {
        title: "Cookie tecnici",
        body: [
          "Il sito puo' usare cookie tecnici necessari per l'area amministrativa, la sicurezza e la gestione della sessione.",
          "Questi cookie sono necessari al funzionamento del servizio e non richiedono consenso preventivo."
        ],
      },
      {
        title: "Local storage",
        body: [
          "Il sito puo' salvare localmente sul dispositivo informazioni di accesso dell'utente, come token e dati profilo, per mantenere la sessione attiva.",
          "Questi dati restano nel browser dell'utente finche' non effettua logout o cancella i dati del browser."
        ],
      },
      {
        title: "Cookie analitici (Google Analytics)",
        body: [
          "Midalot usa Google Analytics 4, un servizio di analisi statistica fornito da Google Ireland Limited, per capire come viene usato il sito (pagine visitate, tempo di permanenza, provenienza del traffico) in forma aggregata.",
          "Questi cookie (es. _ga, _ga_*) non sono tecnici: vengono attivati solo dopo che l'utente ha espresso consenso tramite il banner mostrato alla prima visita.",
          "I dati raccolti sono conservati da Google per un periodo massimo di 14 mesi e possono essere trasferiti verso server negli Stati Uniti, nell'ambito del quadro giuridico previsto dal Data Privacy Framework UE-USA.",
          "L'utente puo' revocare il consenso in qualsiasi momento tramite il pulsante \"Gestisci preferenze cookie\" in fondo a questa pagina, oppure installando il componente aggiuntivo di disattivazione di Google Analytics."
        ],
      },
      {
        title: "Cookie di marketing o profilazione",
        body: [
          "Midalot non usa cookie di profilazione pubblicitaria o remarketing.",
          "Se in futuro verranno aggiunti ulteriori strumenti di marketing, questa pagina sara' aggiornata e verra' richiesto un nuovo consenso esplicito."
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
  const { setConsent } = useCookieConsent()

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

        {type === "cookies" && (
          <button type="button" className="btn btn-outline-secondary mt-3" onClick={() => setConsent(null)}>
            Gestisci preferenze cookie
          </button>
        )}
      </div>
    </section>
  )
}

export default LegalPage
