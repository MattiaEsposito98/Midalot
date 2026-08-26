import { useState } from "react"
import { Link } from "react-router-dom"
import jsPDF from "jspdf"
import styles from "./Regolamento.module.css"

const intro =
  "Il presente regolamento disciplina le modalità di partecipazione, lo svolgimento dei quiz, il funzionamento delle diverse sezioni di gioco, il calcolo dei punteggi, la gestione delle classifiche, la prevenzione delle condotte illecite e l'assegnazione dei premi basata sull'abilità degli utenti sulla piattaforma midalot.live."

const articles = [
  {
    icon: "bi-person-check-fill",
    title: "ART. 1 – Requisiti di accesso, gratuità e struttura delle domande",
    blocks: [
      { type: "p", text: "1. Gratuità e Registrazione Account:" },
      {
        type: "ul",
        items: [
          "La registrazione alla piattaforma e la partecipazione a tutte le modalità di gioco (Quiz One Shot, Midalario e Training) sono completamente gratuite e non comportano alcun costo, acquisto obbligatorio o quota d'iscrizione.",
          "La registrazione al sito e l'accesso ai Quiz One Shot e al Midalario sono riservati agli utenti che abbiano compiuto almeno 14 (quattordici) anni di età. Gli utenti di età compresa tra i 14 e i 17 anni dichiarano di registrarsi e partecipare con l'autorizzazione ed il consenso dei genitori o di chi ne esercita la potestà genitoriale.",
          "Ciascun utente persona fisica può registrare ed utilizzare un solo ed unico account.",
          "Gli utenti ospiti (Guest) possono accedere esclusivamente alla modalità Training.",
        ],
      },
      { type: "p", text: "2. Dichiarazioni Mendaci, Manleva ed Età Falsa:" },
      {
        type: "ul",
        items: [
          "La piattaforma si affida alla buona fede degli utenti che compilano il modulo di registrazione inserendo la propria data di nascita e confermando i requisiti di età. L'utente (ovvero chi ne esercita la responsabilità genitoriale) si assume la piena ed esclusiva responsabilità per eventuali dichiarazioni mendaci o false indicazioni rese in fase di iscrizione.",
          "Midalot.live declina espressamente ogni responsabilità per l'accesso e la registrazione di minori di 14 anni che abbiano falsificato i propri dati anagrafici o raggirato i controlli di sistema. Gli organizzatori si riservano il diritto di sospendere o cancellare immediatamente l'account, annullando retroattivamente i punteggi e gli eventuali premi vinti senza alcun preavviso, non appena venuti a conoscenza del falso.",
        ],
      },
      { type: "p", text: "3. Composizione della domanda: Ogni domanda è composta da un testo principale e da 4 (quattro) opzioni di risposta, di cui una sola è corretta." },
      { type: "p", text: "4. Contenuti multimediali: Le domande possono includere elementi multimediali (immagini, tracce audio o video) che devono essere consultati prima di selezionare la risposta." },
      { type: "p", text: "5. Limiti di tempo: Ogni domanda prevede un tempo massimo visibile a schermo." },
      { type: "p", text: "6. Mancata risposta: Se il timer scade senza che l'utente abbia selezionato una risposta, la domanda viene registrata come \"Non risposta\"." },
    ],
  },
  {
    icon: "bi-controller",
    title: "ART. 2 – Modalità di gioco e premiazioni",
    blocks: [
      { type: "p", text: "2.1. Quiz One Shot" },
      {
        type: "ul",
        items: [
          "Accesso e Partecipazione: I Quiz One Shot sono creati dagli Amministratori e sono aperti a tutti gli utenti registrati sulla piattaforma.",
          "Tentativi consentiti: Ogni quiz One Shot può essere completato una sola volta.",
          "Gestione delle interruzioni: Qualora la sessione si interrompa per qualsiasi motivo (problemi di connessione, chiusura accidentale del browser, ecc.), il sistema chiude automaticamente il tentativo salvando in modo definitivo il punteggio accumulato fino al momento dell'interruzione. Il quiz non potrà essere ripreso: il punteggio registrato al momento dell'interruzione sarà considerato il punteggio finale.",
          "Visualizzazione risposte: Durante lo svolgimento del quiz non viene mostrato l'esito delle singole risposte. Le risposte corrette saranno consultabili esclusivamente al termine del quiz.",
          "Classifiche e Premi Mensili: Le classifiche dei Quiz One Shot comprendono filtri settimanali e mensili e sono sempre visibili. Il punteggio della classifica mensile è determinato dalla somma dei punteggi ottenuti dall'utente in tutti i Quiz One Shot eseguiti nell'arco dello stesso mese solare. L'utente che al termine del mese si posizionerà al 1° posto della classifica mensile riceverà un Buono Regalo Amazon.it del valore di 10,00€.",
        ],
      },
      { type: "p", text: "2.2. Midalario (Quiz Settimanali a Premio in Contemporanea)" },
      {
        type: "ul",
        items: [
          "Descrizione e Svolgimento: Il Midalario è la sezione speciale dedicata ai quiz settimanali a premio. A differenza delle altre modalità, il Midalario si svolge in contemporanea tra tutti i partecipanti a un orario e in un giorno specifici.",
          "Comunicazione di Data e Ora: Gli organizzatori comunicheranno preventivamente la data e l'orario esatto di svolgimento tramite avviso sul sito ufficiale midalot.live e attraverso i propri canali social ufficiali.",
          "Chiusura Iscrizioni e Accesso: Le iscrizioni e l'accesso alla stanza di gioco del Midalario si chiudono tassativamente pochi minuti prima dell'orario di inizio stabilito. Gli utenti che non si saranno collegati entro tale termine non potranno partecipare alla sessione in corso.",
          "Premio per la Competenza: Per premiare l'abilità, la velocità e la preparazione degli utenti, al termine di ciascun quiz del Midalario viene messo in palio per il vincitore (1° classificato) un Buono Regalo Amazon.it del valore di 10,00€.",
          "Visibilità Esiti: Le risposte corrette e l'esito delle singole domande vengono mostrati solamente al termine del quiz e al momento della pubblicazione della classifica finale.",
        ],
      },
      { type: "p", text: "2.3. Training (Allenamento)" },
      {
        type: "ul",
        items: [
          "Struttura: Esercitazioni libere suddivise per categoria, con domande ed opzioni estratte casualmente ad ogni sessione.",
          "Feedback immediato: A differenza dei quiz One Shot e del Midalario, nel Training l'esito viene mostrato subito dopo ogni risposta (verde per la risposta corretta, rosso per l'eventuale errore).",
          "Accessibilità — Utenti registrati: salvano progressi, punteggi e posizione nelle classifiche di categoria (sempre visibili). Ospiti (Guest): possono esercitarsi liberamente senza salvataggio dei dati.",
        ],
      },
    ],
  },
  {
    icon: "bi-calculator-fill",
    title: "ART. 3 – Sistema di punteggio e calcolo dei punti",
    blocks: [
      { type: "p", text: "Il sistema di calcolo del punteggio è uniforme per tutte le modalità di gioco (One Shot, Midalario e Training)." },
      { type: "p", text: "3.1. Risposta Corretta e Bonus Velocità" },
      {
        type: "ul",
        items: [
          "Punteggio Base: Una risposta corretta assegna 70,00 punti base.",
          "Bonus Velocità: Somma da 0,00 a 30,00 punti, calcolata in millisecondi in base al tempo impiegato rispetto al tempo massimo disponibile.",
          "Punteggio Massimo: 100,00 punti per singola risposta corretta (70,00 base + 30,00 bonus massimo per risposta immediata).",
          "Risposta allo scadere: Una risposta corretta data allo scadere del tempo assegna 70,00 punti (0,00 bonus).",
        ],
      },
      { type: "p", text: "Esempio pratico (Domanda con tempo massimo di 10 secondi): risposta corretta in 4,200 secondi → 87,40 punti; risposta corretta in 4,900 secondi → 85,30 punti." },
      { type: "p", text: "3.2. Malus (Penalità)" },
      {
        type: "ul",
        items: [
          "Risposta Sbagliata: Detrazione del 10% dal punteggio totale accumulato fino a quel momento.",
          "Mancata Risposta (Time-out): Detrazione del 5% dal punteggio totale accumulato fino a quel momento.",
        ],
      },
      { type: "p", text: "3.3. Punteggio Minimo (Soglia di Protezione): Il punteggio totale di una sessione non può scendere sotto 0,00 punti. Risposte errate o non date a punteggio zero non generano punteggi negativi." },
    ],
  },
  {
    icon: "bi-trophy-fill",
    title: "ART. 4 – Classifiche, criteri di spareggio e diffusione risultati",
    blocks: [
      { type: "p", text: "1. Calcolo Finale: Il punteggio finale è dato dalla somma dei punti base e dei bonus velocità, al netto dei malus applicati." },
      { type: "p", text: "2. Criteri di Ordinamento e Spareggio: 1° criterio: punteggio totale più alto. 2° criterio (primo spareggio): in caso di parità di punteggio tra due o più utenti, prevale chi ha impiegato il tempo totale di svolgimento inferiore (misurato in millisecondi)." },
      { type: "p", text: "3. Parità Assoluta: Qualora due o più utenti registrino esattamente il medesimo punteggio finale e il medesimo tempo totale al millisecondo, gli utenti interessati si considereranno primi classificati ex aequo e il premio in palio verrà suddiviso in parti uguali tra di essi." },
      { type: "p", text: "4. Consultazione Classifiche:" },
      {
        type: "ul",
        items: [
          "Training: Classifiche sempre visibili per categoria.",
          "Quiz One Shot: Classifiche sempre visibili (con filtri settimanali e mensili).",
          "Midalario: Classifica visibile al termine del quiz per l'assegnazione del premio al vincitore.",
        ],
      },
      { type: "p", text: "5. Divulgazione sui Canali Social: I risultati, i punteggi, le classifiche parziali/finali ed i nickname dei vincitori potranno essere pubblicati, diffusi ed evidenziati sulle pagine ed i canali social ufficiali della piattaforma, inclusi Instagram (@mida.lot) e YouTube." },
    ],
  },
  {
    icon: "bi-shield-fill-exclamation",
    title: "ART. 5 – Fair play, correttezza e divieto di frode",
    blocks: [
      { type: "p", text: "1. Account Multipli: È severamente vietata la creazione di account multipli (account fake o secondari) da parte dello stesso utente. Qualora venissero rilevati account riconducibili alla stessa persona fisica, gli Amministratori si riservano il diritto di sospendere o cancellare tutti i profili coinvolti e annullare i relativi punteggi." },
      { type: "p", text: "2. Utilizzo di Bot e Strumenti Automatici: È vietato qualsiasi tentativo di manomissione del codice di gioco, l'utilizzo di script, bot, estensioni del browser o strumenti di intelligenza artificiale/automazione volti a simulare o accelerare la risposta dell'utente." },
      { type: "p", text: "3. Sanzioni: La violazione delle norme di correttezza comporta l'immediata squalifica dalle classifiche, l'eventuale ban permanente dell'account e la revoca di qualsiasi premio non ancora erogato." },
    ],
  },
  {
    icon: "bi-gift-fill",
    title: "ART. 6 – Condizioni di erogazione e consegna dei premi",
    blocks: [
      { type: "p", text: "1. Natura dei Premi: I premi consistono esclusivamente in Buoni Regalo Amazon.it del valore nominale complessivo di 10,00€, erogati a titolo di riconoscimento del merito e della capacità personale, dell'abilità e della preparazione dimostrata dai vincitori nelle varie sessioni di gioco." },
      { type: "p", text: "2. Inquadramento Legale: La piattaforma opera nel rispetto dell'esenzione dalle manifestazioni a premio ai sensi dell'Art. 6, comma 1, lett. a) e d) del D.P.R. 430/2001, trattandosi di riconoscimento del merito personale e/o di premio di minimo valore in concorsi a partecipazione interamente gratuita." },
      { type: "p", text: "3. Modalità e Tempistiche di Consegna:" },
      {
        type: "ul",
        items: [
          "Il codice alfanumerico del Buono Regalo verrà inviato in formato digitale all'indirizzo email associato all'account del vincitore entro 15 (quindici) giorni lavorativi dalla chiusura della classifica di riferimento (mensile per i Quiz One Shot, settimanale per il Midalario).",
          "Gli organizzatori non si assumono responsabilità in caso di indirizzo e-mail errato, non attivo o di mancata ricezione dovuta a filtri SPAM dell'utente.",
        ],
      },
      { type: "p", text: "4. Vincitori Minorenni e Verifica dell'Identità:" },
      {
        type: "ul",
        items: [
          "Qualora il vincitore di un premio sia un utente minorenne (di età compresa tra 14 e 17 anni), gli organizzatori si riservano il diritto di erogare il premio previa conferma ed autorizzazione formale da parte del genitore o del tutore legale, al quale potrà essere richiesto di indicare l'indirizzo e-mail di destinazione del voucher.",
          "Prima dell'erogazione del premio, la gestione si riserva il diritto di effettuare verifiche sull'età dichiarata e sul rispetto delle regole del profilo unico e del fair play.",
        ],
      },
      { type: "p", text: "5. Convertibilità: I Buoni Regalo non possono in alcun caso essere convertiti in denaro contante, trasferiti ad altri account o sostituiti." },
      { type: "p", text: "6. Ripartizione del Premio: Nel caso di vincita ex aequo prevista dall'Art. 4.3, il valore di 10,00€ verrà equamente suddiviso in Buoni Regalo di importo pari alla quota spettante a ciascun covincitore." },
    ],
  },
  {
    icon: "bi-hdd-network-fill",
    title: "ART. 7 – Limitazione di responsabilità tecnica",
    blocks: [
      { type: "p", text: "1. Connessione dell'utente: Gli organizzatori non sono responsabili per eventuali malfunzionamenti della rete internet, problemi di latenza (lag/ping), cali di velocità o disconnessioni imputabili al provider di rete dell'utente o ai suoi dispositivi." },
      { type: "p", text: "2. Impatto sui Millisecondi: Poiché il bonus velocità si basa sulla misurazione dei millisecondi, ritardi causati da connessioni lente o dispositivi hardware non performanti sono a carico dell'utente e non daranno diritto ad alcun ricalcolo del punteggio." },
      { type: "p", text: "3. Interruzioni dei Server: In caso di manutenzione straordinaria o guasto dei server della piattaforma che impedisca il corretto svolgimento di un quiz in corso, gli Amministratori si riservano il diritto di annullare o ripetere la sessione interessata." },
    ],
  },
  {
    icon: "bi-c-circle-fill",
    title: "ART. 8 – Proprietà intellettuale, privacy e diritti di immagine",
    blocks: [
      { type: "p", text: "1. Copyright: Tutti i contenuti presenti su midalot.live (domande, opzioni, testi, immagini, tracce audio e video) sono di proprietà esclusiva della piattaforma o concessi in licenza d'uso. Ne è vietata la riproduzione, copia o distribuzione non autorizzata." },
      { type: "p", text: "2. Trattamento Dati e Autorizzazione Social: Con la registrazione alla piattaforma e l'accettazione del presente regolamento, l'utente presta il proprio consenso al trattamento dei dati personali in conformità alla Privacy Policy ed autorizza espressamente gli organizzatori alla pubblicazione del proprio nickname, dei punteggi ottenuti, delle posizioni in classifica e delle vincite conseguite sia sul sito midalot.live sia sui canali social ufficiali della piattaforma (inclusi Instagram @mida.lot e YouTube), per finalità trasparenti, informative e promozionali del gioco." },
    ],
  },
  {
    icon: "bi-arrow-repeat",
    title: "ART. 9 – Modifiche al regolamento",
    blocks: [
      { type: "p", text: "1. Gli organizzatori si riservano la facoltà di apportare modifiche o integrazioni al presente regolamento per motivi organizzativi, tecnici o normativi." },
      { type: "p", text: "2. Le eventuali modifiche entreranno in vigore al momento della pubblicazione della versione aggiornata sul sito midalot.live." },
    ],
  },
]

function buildRegolamentoPdf() {
  const doc = new jsPDF({ unit: "pt", format: "a4" })

  const marginX = 56
  const marginTop = 64
  const marginBottom = 56
  const pageWidth = doc.internal.pageSize.getWidth()
  const pageHeight = doc.internal.pageSize.getHeight()
  const contentWidth = pageWidth - marginX * 2

  let y = marginTop

  function ensureSpace(lineHeight) {
    if (y + lineHeight > pageHeight - marginBottom) {
      doc.addPage()
      y = marginTop
    }
  }

  function writeLines(lines, { fontSize, font = "helvetica", style = "normal", color = "#222222", lineGap = 4, indent = 0 }) {
    doc.setFont(font, style)
    doc.setFontSize(fontSize)
    doc.setTextColor(color)
    const lineHeight = fontSize * 1.28 + lineGap

    lines.forEach((line) => {
      ensureSpace(lineHeight)
      doc.text(line, marginX + indent, y)
      y += lineHeight
    })
  }

  function paragraph(text, opts = {}) {
    const indent = opts.indent || 0
    const lines = doc.splitTextToSize(text, contentWidth - indent)
    writeLines(lines, { fontSize: 10.5, ...opts, indent })
    y += 4
  }

  function heading(text) {
    y += 10
    const lines = doc.splitTextToSize(text, contentWidth)
    writeLines(lines, { fontSize: 13, style: "bold", color: "#182033" })
    y += 2
  }

  function bulletList(items) {
    const bulletIndent = 14
    items.forEach((item) => {
      const lines = doc.splitTextToSize(item, contentWidth - bulletIndent)
      const lineHeight = 10.5 * 1.28 + 4
      ensureSpace(lineHeight)
      doc.setFillColor("#222222")
      doc.circle(marginX + 3, y - 3, 1.4, "F")
      writeLines(lines, { fontSize: 10.5, indent: bulletIndent })
    })
    y += 4
  }

  // Title
  doc.setFont("helvetica", "bold")
  doc.setFontSize(18)
  doc.setTextColor("#182033")
  doc.text("REGOLAMENTO UFFICIALE DI GIOCO", pageWidth / 2, y, { align: "center" })
  y += 24
  doc.text("MIDALOT.LIVE", pageWidth / 2, y, { align: "center" })
  y += 22

  doc.setFont("helvetica", "normal")
  doc.setFontSize(10)
  doc.setTextColor("#647084")
  doc.text("Documento ufficiale che disciplina la partecipazione al gioco sulla piattaforma midalot.live", pageWidth / 2, y, { align: "center" })
  y += 16

  doc.setDrawColor("#ffc107")
  doc.setLineWidth(2)
  doc.line(marginX, y, pageWidth - marginX, y)
  y += 20

  doc.setFont("helvetica", "italic")
  paragraph(intro, { style: "italic", color: "#333333" })
  y += 6

  articles.forEach((article) => {
    heading(article.title)

    article.blocks.forEach((block) => {
      if (block.type === "ul") {
        bulletList(block.items)
      } else {
        paragraph(block.text)
      }
    })
  })

  y += 10
  doc.setDrawColor("#dce5ee")
  doc.setLineWidth(0.7)
  ensureSpace(20)
  doc.line(marginX, y, pageWidth - marginX, y)
  y += 16

  doc.setFont("helvetica", "italic")
  doc.setFontSize(8.5)
  doc.setTextColor("#647084")
  doc.text("Ultimo aggiornamento: 26 agosto 2026 — midalot.live", pageWidth / 2, y, { align: "center" })

  const pageCount = doc.internal.getNumberOfPages()
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i)
    doc.setFont("helvetica", "normal")
    doc.setFontSize(8)
    doc.setTextColor("#647084")
    doc.text(`Pagina ${i} di ${pageCount}`, pageWidth / 2, pageHeight - 24, { align: "center" })
  }

  return doc
}

function Regolamento() {
  const [downloading, setDownloading] = useState(false)

  function handleDownloadPdf() {
    setDownloading(true)

    try {
      const doc = buildRegolamentoPdf()
      doc.save("Regolamento_Midalot.pdf")
    } finally {
      setDownloading(false)
    }
  }

  return (
    <section className={styles.page}>
      <div className={`container ${styles.wrap}`}>
        <Link to="/" className={styles.backLink}>
          <i className="bi bi-arrow-left"></i>
          Torna alla home
        </Link>

        <header className={styles.header}>
          <div className={styles.headerTop}>
            <span className={styles.eyebrow}>
              <i className="bi bi-journal-check"></i>
              Regolamento ufficiale di gioco
            </span>
            <button
              type="button"
              className={`btn btn-outline-primary btn-sm ${styles.downloadBtn}`}
              onClick={handleDownloadPdf}
              disabled={downloading}
            >
              <i className="bi bi-file-earmark-pdf"></i>
              {downloading ? "Preparazione..." : "Scarica PDF"}
            </button>
          </div>
          <h1>Regolamento ufficiale di gioco – midalot.live</h1>
          <p className={styles.intro}>{intro}</p>
        </header>

        <div className={styles.sections}>
          {articles.map((article) => (
            <article className={styles.section} key={article.title}>
              <div className={styles.sectionHeading}>
                <span className={styles.sectionIcon}>
                  <i className={`bi ${article.icon}`}></i>
                </span>
                <h2>{article.title}</h2>
              </div>
              {article.blocks.map((block, index) =>
                block.type === "ul" ? (
                  <ul key={index}>
                    {block.items.map((item) => (
                      <li key={item}>{item}</li>
                    ))}
                  </ul>
                ) : (
                  <p key={index}>{block.text}</p>
                )
              )}
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
