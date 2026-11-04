import { useAuth } from "../../context/useAuth"
import css from "./XpBar.module.css"

function LevelStar({ level, large = false }) {
  return (
    <span className={`${css.star} ${large ? css.starLarge : ""}`} aria-label={`Livello ${level}`}>
      <i className="bi bi-star-fill"></i>
      <b>{level}</b>
    </span>
  )
}

// variant "navbar": barra compatta per la navbar; "card": versione estesa con
// il dettaglio degli XP mancanti al livello successivo.
function XpBar({ variant = "card", xp: xpProp }) {
  const { user } = useAuth()
  const xp = xpProp || user?.xp_info

  if (!xp) return null

  const progress = Math.min(100, Math.max(0, xp.progress))

  if (variant === "navbar") {
    return (
      <div
        className={css.navbarXp}
        title={`Livello ${xp.level} · mancano ${xp.remaining} XP al livello ${xp.level + 1}`}
      >
        <LevelStar level={xp.level} />
        <div className={css.navTrack}>
          <div className={css.fill} style={{ width: `${progress}%` }}></div>
          <span className={css.navText}>
            {xp.into_level}/{xp.level_span}
          </span>
        </div>
      </div>
    )
  }

  return (
    <div className={css.card}>
      <div className={css.cardHeader}>
        <LevelStar level={xp.level} large />
        <div>
          <strong className={css.cardTitle}>Livello {xp.level}</strong>
          <span className={css.cardSub}>{xp.total} XP totali</span>
        </div>
      </div>

      <div className={css.track}>
        <div className={css.fill} style={{ width: `${progress}%` }}></div>
        <span className={css.trackText}>
          {xp.into_level} / {xp.level_span}
        </span>
      </div>

      <div className={css.cardFooter}>
        <span>Mancano {xp.remaining} XP al livello {xp.level + 1}</span>
      </div>
    </div>
  )
}

export default XpBar
