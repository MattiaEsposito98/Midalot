import { useEffect } from "react"
import { Navigate, useParams } from "react-router-dom"

import { captureCampaign } from "../../utils/acquisition"
import { API_BASE } from "../../service/api"
import { logError } from "../../utils/logger"

// Per non contare due volte la stessa apertura quando React rimonta la pagina (solo in memoria, nessun salvataggio).
let lastCounted = ""

/**
 * Conteggio ANONIMO dell'apertura: invia solo l'etichetta della campagna. Niente cookie, niente
 * identificativi: il server somma un'apertura per campagna e giorno (vedi informativa privacy).
 */
function countOpen(campaign) {
  if (lastCounted === campaign) return
  lastCounted = campaign

  // Se chi apre e' collegato al sito (admin o team) il server non conta l'apertura: serve solo il token, nulla viene salvato.
  let token = null
  try {
    token = localStorage.getItem("token")
  } catch {
    token = null
  }

  fetch(`${API_BASE}/api/campaign-visit`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify({ campaign }),
    credentials: "omit",
    keepalive: true,
  }).catch(logError)
}

/**
 * Indirizzo breve da mettere nei messaggi ai clienti, senza parametri visibili:
 *  - /ig/gioielleria   (messaggi diretti Instagram)  -> origine "instagram / dm", campagna "clienti_gioielleria"
 *  - /mail/gioielleria (email alle aziende)          -> origine "email / outreach", campagna "email_gioielleria"
 * Registra l'origine, conta l'apertura in forma anonima e porta alla pagina dei servizi.
 * Non e' indicizzabile (vedi useRouteSeo: solo le pagine in elenco lo sono).
 */
export default function CampagnaDm({ source = "instagram", medium = "dm", prefix = "clienti" }) {
  const { sector } = useParams()

  // Gli effetti dei figli partono prima di quelli di App: l'origine e' gia' registrata
  // quando App legge la provenienza, che quindi non la sovrascrive.
  useEffect(() => {
    const tag = sector ? String(sector).replace(/-/g, "_") : ""
    const campaign = tag ? `${prefix}_${tag}` : prefix

    captureCampaign({ source, medium, campaign })
    countOpen(campaign.toLowerCase().replace(/[^a-z0-9_.-]/g, "").slice(0, 49))
  }, [sector, source, medium, prefix])

  return <Navigate to="/servizi-digitali" replace />
}
