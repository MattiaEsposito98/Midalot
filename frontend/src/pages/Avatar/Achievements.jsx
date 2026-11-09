import { useEffect, useState } from "react"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { RARITY } from "../../components/Avatar3D/catalog"
import styles from "./Avatar.module.css"

const SLOT_WORD = { title: "Titolo", frame: "Cornice", hands: "Guanti", back: "Mantello" }

function formatDate(iso) {
  return new Date(iso).toLocaleDateString("it-IT", { day: "numeric", month: "short", year: "numeric" })
}

/**
 * Elenco degli obiettivi con avanzamento e premi (monete e oggetti esclusivi).
 */
export default function Achievements() {
  const [list, setList] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    let alive = true
    api
      .get("/avatar/achievements")
      .then((res) => alive && setList(res.data.achievements))
      .catch((err) => {
        logError(err)
        if (alive) setError("Non riesco a caricare gli obiettivi.")
      })
    return () => {
      alive = false
    }
  }, [])

  if (error) return <div className={styles.panel}>{error}</div>
  if (!list) return <div className={styles.panel}>Caricamento obiettivi...</div>

  const done = list.filter((a) => a.done).length
  // Prima quelli da completare (i piu' vicini in cima), poi quelli raggiunti.
  const sorted = [...list].sort((a, b) => a.done - b.done || b.progress / b.target - a.progress / a.target)

  return (
    <div className={styles.panel}>
      <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div>
          <div className={styles.label}>Obiettivi</div>
          <p className={styles.hint + " mb-0"}>Raggiungi i traguardi giocando: ogni obiettivo regala monete e alcuni anche oggetti esclusivi.</p>
        </div>
        <span className={styles.levelTag}>
          {done}/{list.length}
        </span>
      </div>

      <div className={styles.goalGrid}>
        {sorted.map((a) => {
          const pct = Math.round((a.progress / a.target) * 100)
          const rarity = a.item ? RARITY[a.item.rarity] || RARITY.comune : null

          return (
            <div key={a.key} className={`${styles.goal} ${a.done ? styles.goalDone : ""}`}>
              <div className={styles.goalIcon}>
                <i className={`bi ${a.done ? "bi-patch-check-fill" : a.icon}`}></i>
              </div>
              <div className="flex-grow-1">
                <div className={styles.itemName}>{a.name}</div>
                <div className={styles.itemLevel}>{a.description}</div>

                {a.done ? (
                  <div className={styles.goalDate}>
                    <i className="bi bi-check-circle-fill"></i> Raggiunto il {formatDate(a.achieved_at)}
                  </div>
                ) : (
                  <div className={styles.goalBar} aria-label={`${a.progress} su ${a.target}`}>
                    <div style={{ width: `${pct}%` }}></div>
                    <span>
                      {a.progress}/{a.target}
                    </span>
                  </div>
                )}

                <div className={styles.goalRewards}>
                  {a.coins > 0 && (
                    <span className={styles.coins}>
                      <i className="bi bi-coin"></i> {a.coins}
                    </span>
                  )}
                  {a.item && (
                    <span className={styles.goalItem} style={{ "--rarity": rarity.color }}>
                      <i className="bi bi-gift"></i> {SLOT_WORD[a.item.slot] || "Oggetto"}: {a.item.name}
                    </span>
                  )}
                </div>
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}
