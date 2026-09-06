# Backup del database di produzione

## Come si lancia

Da **Git Bash** (o un altro terminale con `bash`), sul tuo computer — **mai dal
browser, mai da un URL del sito**:

```bash
cd "D:\Progetti autonomi\midalot\backend\scripts"
./backup-produzione.sh
```

Se la prima volta dà un errore di permessi, esegui una volta sola:

```bash
chmod +x backup-produzione.sh
```

## Cosa fa

1. Carica `backup-db.php` sul server (temporaneamente).
2. Lo esegue: crea un dump completo del database in una cartella temporanea del server.
3. Scarica il dump sul tuo computer, dentro `midalot/backups/`.
4. Verifica che il file scaricato sia identico a quello creato sul server (confronto md5).
5. Cancella i file temporanei dal server. Sul tuo computer non cancella nulla.

Alla fine vedrai un messaggio tipo:

```
Backup completato: .../backups/backup-produzione-20260906-104806.sql (1727 KB)
```

## Dove finiscono i file

Nella cartella `midalot/backups/`, che è esclusa da git (non finiscono mai su
GitHub). Contengono i dati di **tutti** gli utenti registrati — email,
nickname, password (cifrate), punteggi — quindi:

- non vanno condivisi, allegati a email, caricati altrove
- puoi cancellarli quando vuoi liberare spazio, tenendone sempre almeno uno recente

## Ogni quanto farlo

Consigliato: **una volta a settimana**, o comunque prima di ogni modifica
importante al sito (nuova funzionalità, migration, pulizia dati). Non c'è
automazione: va lanciato a mano quando decidi tu.

## Come si ripristina, in caso di bisogno

```bash
mysql -u NOME_UTENTE -p NOME_DATABASE < backups/backup-produzione-TIMESTAMP.sql
```

Da fare solo se necessario, e solo dopo aver capito bene cosa si sta
sovrascrivendo: sostituisce interamente le tabelle presenti.
