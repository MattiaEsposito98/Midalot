import { Link } from "react-router-dom"
import styles from "./Regolamento.module.css"

const sections = [
  {
    icon: "bi-pencil-square",
    title: "Creazione e assegnazione dei quiz",
    paragraphs: [
      "I quiz vengono creati dagli amministratori, che definiscono titolo, descrizione, domande, risposte disponibili, risposta corretta e tempo massimo per ogni domanda.",
      "I quiz classici devono essere associati dall'amministratore agli utenti autorizzati. Un utente vede nella propria dashboard solamente i quiz che gli sono stati assegnati.",
      "Ogni quiz classico può essere completato una sola volta. Un tentativo iniziato può essere ripreso, ma dopo il completamento il risultato diventa definitivo.",
    ],
  },
  {
    icon: "bi-lightning-charge-fill",
    title: "Training",
    paragraphs: [
      "I training sono esercitazioni organizzate per categoria. Le domande vengono estratte casualmente secondo le impostazioni definite dall'amministratore.",
      "Le risposte sono mostrate in ordine casuale. Dopo ogni risposta il training mostra subito l'esito: verde per la risposta corretta e rosso per l'eventuale risposta sbagliata selezionata.",
      "Gli utenti registrati salvano risultati, progressi e posizioni in classifica. Gli ospiti possono esercitarsi, ma il risultato della sessione non viene salvato.",
    ],
  },
  {
    icon: "bi-images",
    title: "Tipi di domanda",
    paragraphs: [
      "Una domanda contiene sempre un testo e quattro possibili risposte, delle quali una sola è corretta.",
      "Per rendere il quiz più completo, una domanda può includere anche un'immagine, una traccia audio oppure un video. Il contenuto multimediale fa parte della domanda e deve essere osservato o ascoltato prima di rispondere.",
      "Ogni domanda ha un tempo massimo visibile durante lo svolgimento. Se il tempo termina senza una risposta, la domanda viene registrata come non risposta.",
    ],
  },
  {
    icon: "bi-calculator-fill",
    title: "Calcolo del punteggio",
    paragraphs: [
      "Il punteggio utilizza la stessa formula nei quiz classici e nei training. Una risposta corretta assegna 70,00 punti base più un bonus velocità compreso tra 0,00 e 30,00 punti.",
      "Il bonus velocità viene calcolato usando il tempo massimo della domanda e il tempo impiegato dall'utente, misurato in millisecondi. Rispondere correttamente più velocemente produce quindi un punteggio maggiore.",
      "Il punteggio massimo ottenibile con una singola risposta corretta è 100,00 punti. Rispondendo correttamente allo scadere del tempo si ricevono 70,00 punti.",
    ],
  },
  {
    icon: "bi-dash-circle-fill",
    title: "Malus",
    paragraphs: [
      "Una risposta sbagliata sottrae il 10% del punteggio accumulato fino a quel momento.",
      "Una domanda non risposta entro il tempo disponibile sottrae il 5% del punteggio accumulato. Il malus è inferiore rispetto a quello applicato per una risposta sbagliata.",
      "Il punteggio totale non può mai scendere sotto 0,00. Se l'utente non ha ancora ottenuto punti, una risposta sbagliata o non data non produce un punteggio negativo.",
    ],
  },
  {
    icon: "bi-trophy-fill",
    title: "Completamento e classifiche",
    paragraphs: [
      "Al termine del quiz, il punteggio finale è dato dalla somma dei punti ottenuti e dei malus applicati a tutte le domande.",
      "Le classifiche ordinano prima gli utenti con il punteggio più alto. In caso di parità esatta, viene favorito chi ha impiegato meno tempo totale, calcolato in millisecondi.",
      "Per i quiz classici la classifica è visibile solamente quando l'amministratore la abilita. Le classifiche training riguardano invece la categoria di allenamento.",
    ],
  },
]

function Regolamento() {
  return (
    <section className={styles.page}>
      <div className={`container ${styles.wrap}`}>
        <Link to="/dashboard" className={styles.backLink}>
          <i className="bi bi-arrow-left"></i>
          Torna ai Quiz One Shot
        </Link>

        <header className={styles.header}>
          <span className={styles.eyebrow}>
            <i className="bi bi-journal-check"></i>
            Regolamento Midalot
          </span>
          <h1>Come funzionano quiz, training e punteggi</h1>
          <p>
            Questa pagina descrive il percorso completo di un quiz: dalla creazione delle domande
            fino al completamento, al calcolo del risultato e alla classifica.
          </p>
        </header>

        <div className={styles.formulaCard}>
          <div>
            <span className={styles.formulaLabel}>Risposta corretta</span>
            <strong>70,00 + bonus velocità fino a 30,00</strong>
          </div>
          <div>
            <span className={styles.formulaLabel}>Risposta sbagliata</span>
            <strong>-10% del punteggio attuale</strong>
          </div>
          <div>
            <span className={styles.formulaLabel}>Non risposta</span>
            <strong>-5% del punteggio attuale</strong>
          </div>
          <div>
            <span className={styles.formulaLabel}>Punteggio minimo</span>
            <strong>0,00 punti</strong>
          </div>
        </div>

        <article className={styles.example}>
          <div className={styles.exampleIcon}>
            <i className="bi bi-stopwatch-fill"></i>
          </div>
          <div>
            <h2>Esempio con una domanda da 10 secondi</h2>
            <p>
              Una risposta corretta data in 4,200 secondi assegna 87,40 punti. La stessa risposta
              data in 4,900 secondi assegna 85,30 punti. La precisione in millisecondi permette di
              premiare correttamente la risposta più veloce.
            </p>
          </div>
        </article>

        <div className={styles.sections}>
          {sections.map((section) => (
            <article className={styles.section} key={section.title}>
              <div className={styles.sectionHeading}>
                <span className={styles.sectionIcon}>
                  <i className={`bi ${section.icon}`}></i>
                </span>
                <h2>{section.title}</h2>
              </div>
              {section.paragraphs.map((paragraph) => (
                <p key={paragraph}>{paragraph}</p>
              ))}
            </article>
          ))}
        </div>

        <div className={styles.reportNotice}>
          <i className="bi bi-flag-fill"></i>
          <p>
            Durante un training è possibile usare il pulsante <strong>Segnala questa domanda</strong>{" "}
            per comunicare contenuti errati, non aggiornati o poco chiari.
          </p>
        </div>
      </div>
    </section>
  )
}

export default Regolamento
