# Midalot

Midalot e una piattaforma quiz composta da un frontend React per gli utenti e da un backend Laravel riservato agli amministratori. Il progetto permette di creare quiz, assegnarli agli utenti registrati e offrire anche quiz training pubblici giocabili dagli ospiti.

L'obiettivo del progetto e unire una gestione admin semplice e ordinata con un'esperienza utente leggera, giocosa e adatta a quiz rapidi, classifiche e progressi personali.

## Cosa Offre

- Quiz assegnati agli utenti registrati.
- Quiz training pubblici accessibili anche senza registrazione.
- Categorie training gestibili dall'amministratore, ad esempio Anime, Sport, Calcio o Cinema.
- Punteggio basato su risposte corrette e velocita.
- Salvataggio dei progressi per gli utenti registrati.
- Classifiche per quiz e categorie training.
- Area admin dedicata, separata dall'esperienza utente.
- Registrazione e login utenti tramite frontend React.
- Backend web riservato esclusivamente agli admin.

## Struttura Del Progetto

Il progetto e diviso in due parti principali:

- `frontend`: applicazione React/Vite usata dagli utenti finali.
- `backend`: applicazione Laravel con API, autenticazione, gestione quiz e area admin.

Il frontend comunica con il backend tramite API. Gli utenti normali non usano il pannello Laravel, mentre gli admin accedono dal backend per gestire quiz, categorie, domande, utenti associati e classifiche.

## Frontend Utente

Il frontend React e pensato per gli utenti che vogliono registrarsi, accedere, giocare quiz e consultare i propri progressi.

Funzionalita principali:

- Home pubblica Midalot.
- Registrazione e login utente.
- Profilo personale.
- Quiz assegnati all'utente.
- Sezione Training pubblica e privata.
- Storico e progressi training per utenti registrati.
- Pagine legali: Privacy, Termini e Cookie.
- Meta tag SEO, manifest e base pronta per l'indicizzazione online.

## Backend Admin

Il backend Laravel e un portale riservato agli amministratori. Gli utenti normali non possono usare l'area web backend e vengono indirizzati al frontend React.

Funzionalita principali:

- Login admin only.
- Dashboard amministrativa.
- Gestione quiz classici assegnati.
- Creazione e modifica quiz.
- Gestione domande e risposte.
- Associazione utenti ai quiz.
- Classifiche e visibilita classifiche.
- Gestione categorie training.
- Gestione quiz training.
- Profilo admin.

## Quiz Classici

I quiz classici sono pensati per essere assegnati a utenti registrati. L'admin crea il quiz, aggiunge domande e risposte, associa gli utenti e puo consultare le classifiche.

Questa parte e separata dai quiz training, cosi la logica dei quiz assegnati resta chiara e controllata.

## Quiz Training

I quiz training sono quiz di allenamento organizzati per categoria.

Caratteristiche:

- Possono essere giocati anche da utenti non registrati.
- Gli ospiti vedono il risultato finale ma non salvano progressi.
- Gli utenti registrati salvano tentativi, punteggi e storico.
- L'admin puo scegliere il numero di domande da estrarre: 5, 10 o tutte.
- Le domande vengono selezionate in modo casuale.
- Un training non attivo o senza abbastanza domande non viene mostrato come giocabile.

## Sicurezza

Sono state applicate diverse misure di base per preparare il progetto a un futuro deploy:

- Backend web riservato agli amministratori.
- Rotte admin protette da autenticazione e controllo admin.
- API utente protette con Sanctum dove necessario.
- Email verificata richiesta per le aree protette.
- Limitazione dei tentativi su login, registrazione, reset password e alcune API pubbliche.
- Password salvate con hashing Laravel.
- Revoca dei token dopo cambio o reset password.
- CORS configurato per consentire solo domini previsti.
- Header di sicurezza HTTP lato backend.
- Registrazione web Laravel disabilitata per utenti normali.

## Privacy E GDPR

Il progetto riduce i dati personali richiesti agli utenti e include pagine base per privacy, termini e cookie.

Sono gia presenti:

- Informativa privacy.
- Termini di utilizzo.
- Informativa cookie.
- Checkbox di accettazione privacy e termini in registrazione.
- Salvataggio della data di accettazione.

Prima della pubblicazione online sara necessario completare i testi legali con i dati reali del titolare, dominio, contatti, tempi di conservazione e fornitori usati in produzione.

## SEO E Preparazione Online

Il frontend include una base SEO pronta per essere completata quando sara disponibile il dominio definitivo:

- Titolo e descrizione del sito.
- Meta tag social.
- Manifest web app.
- `robots.txt`.
- Template sitemap in `frontend/docs/sitemap.template.xml`.
- Checklist SEO in `frontend/SEO_PRODUCTION_CHECKLIST.md`.

Quando il dominio sara deciso, andranno aggiornati sitemap, canonical URL, immagini social pubbliche e Google Search Console.

## Avvio Locale

Frontend:

```bash
cd frontend
npm install
npm run dev
```

Backend:

```bash
cd backend
composer install
npm install
php artisan migrate
php artisan serve
npm run dev
```

In locale il frontend usa normalmente:

```text
http://localhost:5173
```

Il backend Laravel usa normalmente:

```text
http://localhost:8000
```

## Verifiche Consigliate

Prima del deploy definitivo:

- Eseguire test backend.
- Eseguire lint e build frontend.
- Verificare login admin.
- Verificare registrazione e login utente da React.
- Verificare quiz assegnati.
- Verificare quiz training da ospite e da utente registrato.
- Aggiornare testi legali con dati reali.
- Aggiornare `.env` di produzione.
- Configurare dominio, HTTPS, email SMTP e backup database.
- Collegare Google Search Console.

## Stato Del Progetto

Midalot e gia strutturato con una divisione chiara tra esperienza utente e gestione amministrativa. Le funzionalita principali sono presenti: quiz classici, quiz training, progressi, classifiche, area admin, sicurezza di base, pagine legali e preparazione SEO.

Il prossimo passo naturale sara la configurazione dell'ambiente di produzione quando sara scelto il server e il dominio ufficiale.
