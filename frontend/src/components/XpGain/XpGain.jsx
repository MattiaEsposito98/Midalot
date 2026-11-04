import { useEffect } from "react"
import { useAuth } from "../../context/useAuth"
import XpBar from "../XpBar/XpBar"
import css from "./XpGain.module.css"

// Riepilogo XP mostrato nelle schermate di fine partita. `xp` e' lo stato
// aggiornato restituito dal backend: viene anche propagato al contesto, cosi'
// navbar e Home si aggiornano senza ricaricare. Con `refresh` (es. Midalario,
// dove gli XP vengono assegnati a fine evento) lo stato si ricarica dal server.
function XpGain({ awarded, xp, alreadyEarnedHint, refresh = false }) {
  const { user, updateXp, refreshUser } = useAuth()

  useEffect(() => {
    if (xp) updateXp(xp)
  }, [xp, updateXp])

  useEffect(() => {
    if (refresh) refreshUser()
  }, [refresh, refreshUser])

  const shown = xp || (refresh ? user?.xp_info : null)

  if (!shown && !awarded && !alreadyEarnedHint) return null

  return (
    <div className={css.box}>
      {awarded > 0 ? (
        <strong className={css.gain}>
          <i className="bi bi-stars"></i> +{awarded} XP
        </strong>
      ) : (
        alreadyEarnedHint && <span className={css.hint}>{alreadyEarnedHint}</span>
      )}
      {shown && <XpBar variant="card" xp={shown} />}
    </div>
  )
}

export default XpGain
