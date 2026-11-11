// Chiave della partita giocata da ospite a una sfida ricevuta con un link
// (/sfida-amico/:token): resta nel browser finche' l'ospite non si registra.
export const guestKeyStore = (token) => `midalot_guest_${token}`

export function readGuestKey(token) {
  try {
    return token ? localStorage.getItem(guestKeyStore(token)) : null
  } catch {
    return null
  }
}

// Chiave della partita: quella gia' salvata, oppure una nuova generata qui
// prima di iniziare, cosi' le richieste ripetute riprendono la stessa partita.
export function ensureGuestKey(token) {
  let key = readGuestKey(token)
  if (key) return key
  const bytes = new Uint8Array(16)
  crypto.getRandomValues(bytes)
  key = Array.from(bytes, (b) => b.toString(16).padStart(2, "0")).join("")
  try {
    localStorage.setItem(guestKeyStore(token), key)
  } catch {
    // senza archivio la partita resta valida finche' la pagina e' aperta
  }
  return key
}
