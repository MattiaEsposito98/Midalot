#!/bin/bash
# Backup del database di produzione: da lanciare dal proprio computer
# (Git Bash / terminale), MAI dal browser e MAI da un URL del sito.
#
# Cosa fa, in ordine:
#   1. carica scripts/backup-db.php sul server
#   2. lo esegue (crea un dump del database in una cartella temporanea)
#   3. scarica il dump in locale, dentro backend/../backups/
#   4. verifica che il file scaricato sia integro
#   5. cancella il dump e lo script dal server
#
# Uso:
#   cd backend/scripts
#   ./backup-produzione.sh

set -euo pipefail

SSH_HOST="midalot_mattia_backend@midalot.live"
SSH_PORT=222
SSH_KEY="$HOME/.ssh/midalot_key"
REMOTE_DIR="~/web/midalot-backend"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUPS_DIR="$SCRIPT_DIR/../../backups"
mkdir -p "$BACKUPS_DIR"

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
REMOTE_FILE="backup-${TIMESTAMP}.sql"
LOCAL_FILE="$BACKUPS_DIR/backup-produzione-${TIMESTAMP}.sql"

echo "1/5  Carico lo script di backup sul server..."
cat "$SCRIPT_DIR/backup-db.php" | ssh -p "$SSH_PORT" -i "$SSH_KEY" -o BatchMode=yes "$SSH_HOST" \
  "cat > ${REMOTE_DIR}/backup-db-tmp.php"

echo "2/5  Eseguo il dump sul server..."
ssh -p "$SSH_PORT" -i "$SSH_KEY" -o BatchMode=yes "$SSH_HOST" \
  "cd ${REMOTE_DIR} && php backup-db-tmp.php /tmp/${REMOTE_FILE}"

REMOTE_MD5=$(ssh -p "$SSH_PORT" -i "$SSH_KEY" -o BatchMode=yes "$SSH_HOST" \
  "md5sum /tmp/${REMOTE_FILE} | cut -d' ' -f1")

echo "3/5  Scarico il backup in locale..."
ssh -p "$SSH_PORT" -i "$SSH_KEY" -o BatchMode=yes "$SSH_HOST" \
  "cat /tmp/${REMOTE_FILE}" > "$LOCAL_FILE"

echo "4/5  Verifico l'integrità del file scaricato..."
if command -v md5sum >/dev/null 2>&1; then
  LOCAL_MD5=$(md5sum "$LOCAL_FILE" | cut -d' ' -f1)
else
  LOCAL_MD5=$(md5 -q "$LOCAL_FILE")
fi

if [ "$REMOTE_MD5" != "$LOCAL_MD5" ]; then
  echo "ERRORE: il file scaricato non corrisponde a quello sul server (md5 diversi)."
  echo "  server: $REMOTE_MD5"
  echo "  locale: $LOCAL_MD5"
  echo "Il backup NON è affidabile: non cancello nulla dal server, controlla a mano."
  exit 1
fi

echo "5/5  Pulisco i file temporanei dal server..."
ssh -p "$SSH_PORT" -i "$SSH_KEY" -o BatchMode=yes "$SSH_HOST" \
  "rm -f ${REMOTE_DIR}/backup-db-tmp.php /tmp/${REMOTE_FILE}"

SIZE_KB=$(du -k "$LOCAL_FILE" | cut -f1)
echo ""
echo "Backup completato: $LOCAL_FILE (${SIZE_KB} KB)"
echo "Questo file resta solo sul tuo computer e non va condiviso: contiene i dati di tutti gli utenti."
