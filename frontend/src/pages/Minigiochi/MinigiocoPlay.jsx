import { useEffect, useState } from "react"
import { useParams } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import { API_BASE } from "../../service/api"
import { logError } from "../../utils/logger"
import shared from "./TastieraRotta.module.css"
import TastieraRotta from "./TastieraRotta"
import SaltoTemporale from "./SaltoTemporale"
import TrovaIntruso from "./TrovaIntruso"

/**
 * Sceglie il componente di gioco giusto in base al tipo del minigioco.
 * In caso di errore nel recupero del tipo, ripiega su un componente di
 * default: il suo stesso hook rifarà la stessa chiamata e mostrerà una
 * schermata di errore completa (con pulsante di ritorno) se qualcosa non va.
 */
function MinigiocoPlay() {
  const { id } = useParams()
  const { token } = useAuth()

  const [tipo, setTipo] = useState(undefined)

  useEffect(() => {
    let cancelled = false

    async function loadTipo() {
      try {
        const res = await fetch(`${API_BASE}/api/minigiochi/${id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        })
        const data = await res.json()

        if (!cancelled) {
          setTipo(data?.minigioco?.tipo || "tastiera_rotta")
        }
      } catch (err) {
        if (!cancelled) {
          logError(err)
          setTipo("tastiera_rotta")
        }
      }
    }

    loadTipo()

    return () => {
      cancelled = true
    }
  }, [id, token])

  if (tipo === undefined) {
    return (
      <div className={shared.centerBox}>
        <div className="spinner-border text-primary"></div>
        <p className={shared.centerText}>Caricamento minigioco...</p>
      </div>
    )
  }

  if (tipo === "salto_temporale") return <SaltoTemporale />
  if (tipo === "trova_intruso") return <TrovaIntruso />
  return <TastieraRotta />
}

export default MinigiocoPlay
