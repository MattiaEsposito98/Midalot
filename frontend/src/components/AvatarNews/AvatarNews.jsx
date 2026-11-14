import { useEffect, useRef, useState } from "react"
import { useLocation, useNavigate } from "react-router-dom"
import api from "../../service/api"
import { useAuth } from "../../context/useAuth"
import { RARITY } from "../Avatar3D/catalog"
import css from "./AvatarNews.module.css"

/**
 * Avviso globale "Obiettivo raggiunto!" / "Hai sbloccato nuovi oggetti!":
 * si controlla all'accesso e dopo ogni partita (cambiano gli XP totali).
 */
export default function AvatarNews() {
  const { user, token, refreshUser } = useAuth()
  const location = useLocation()
  const navigate = useNavigate()
  const [news, setNews] = useState({ items: [], achievements: [], prizes: [] })
  const prevXp = useRef(null)

  const xpTotal = user?.xp_info?.total
  const onAvatarPage = location.pathname.startsWith("/avatar")

  useEffect(() => {
    if (!token || !user?.id || onAvatarPage) return
    let alive = true

    api
      .get("/avatar/news")
      .then((res) => {
        if (!alive) return
        const next = { items: res.data.items || [], achievements: res.data.achievements || [], prizes: res.data.prizes || [] }
        setNews(next)
        // Obiettivi, livelli e premi portano monete: aggiorna il saldo in navbar.
        if (next.achievements.length || next.prizes.length) refreshUser?.()
      })
      .catch(() => {})

    if (prevXp.current != null && xpTotal !== prevXp.current) refreshUser?.()
    prevXp.current = xpTotal

    return () => {
      alive = false
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token, user?.id, xpTotal])

  const { items, achievements, prizes } = news
  if ((!items.length && !achievements.length && !prizes.length) || onAvatarPage) return null

  const markSeen = () => {
    setNews({ items: [], achievements: [], prizes: [] })
    api.post("/avatar/news/seen").catch(() => {})
  }

  const open = () => {
    markSeen()
    navigate(achievements.length && !items.length && !prizes.length ? "/avatar?vista=obiettivi" : "/avatar")
  }

  const shownItems = items.slice(0, 5)
  const title = prizes.length
    ? "Hai vinto un premio!"
    : achievements.length
    ? achievements.length === 1
      ? "Obiettivo raggiunto!"
      : `${achievements.length} obiettivi raggiunti!`
    : items.length === 1
      ? "Nuovo oggetto sbloccato!"
      : `Hai sbloccato ${items.length} nuovi oggetti!`

  return (
    <div className={css.backdrop} role="dialog" aria-modal="true" aria-labelledby="avatar-news-title">
      <div className={css.box}>
        <div className={css.gift}>
          <i className={`bi ${prizes.length ? "bi-trophy-fill" : achievements.length ? "bi-award-fill" : "bi-gift-fill"}`}></i>
        </div>
        <h2 id="avatar-news-title" className={css.title}>
          {title}
        </h2>

        {prizes.length > 0 && (
          <ul className={css.list}>
            {prizes.map((p) => (
              <li key={`${p.kind}-${p.ref}`} style={{ "--rarity": "#e0a800" }}>
                <i className={`bi bi-trophy-fill ${css.goalIcon}`} style={{ color: "#e0a800" }}></i>
                <span>
                  <strong>{p.label}</strong>
                  <span className={css.goalDesc}>{p.position}° posto</span>
                  <span className={css.goalReward}>
                    <i className="bi bi-coin"></i> +{p.coins} monete
                  </span>
                </span>
              </li>
            ))}
          </ul>
        )}

        {achievements.length > 0 && (
          <ul className={css.list}>
            {achievements.map((a) => (
              <li key={a.key} style={{ "--rarity": "#2f9b5a" }}>
                <i className={`bi ${a.icon} ${css.goalIcon}`}></i>
                <span>
                  <strong>{a.name}</strong>
                  <span className={css.goalDesc}>{a.description}</span>
                  <span className={css.goalReward}>
                    {a.coins > 0 && (
                      <>
                        <i className="bi bi-coin"></i> +{a.coins} monete
                      </>
                    )}
                    {a.item && <> · {a.item.name}</>}
                  </span>
                </span>
              </li>
            ))}
          </ul>
        )}

        {items.length > 0 && (
          <>
            <p className={css.sub}>
              {achievements.length ? "E salendo di livello hai ricevuto:" : "Salendo di livello hai ricevuto nuovi oggetti per il tuo avatar."}
            </p>
            <ul className={css.list}>
              {shownItems.map((it) => (
                <li key={it.key} style={{ "--rarity": (RARITY[it.rarity] || RARITY.comune).color }}>
                  <span className={css.rarity}>{(RARITY[it.rarity] || RARITY.comune).label}</span>
                  <strong>{it.name}</strong>
                  {it.unlock_level && <span className={css.level}>Lv {it.unlock_level}</span>}
                </li>
              ))}
            </ul>
            {items.length > shownItems.length && <p className={css.more}>... e altri {items.length - shownItems.length}</p>}
          </>
        )}

        <div className={css.actions}>
          <button type="button" className={css.primary} onClick={open}>
            {items.length ? "Indossali ora" : prizes.length && !achievements.length ? "Vai al negozio" : "Vedi i tuoi obiettivi"}
          </button>
          <button type="button" className={css.secondary} onClick={markSeen}>
            Più tardi
          </button>
        </div>
      </div>
    </div>
  )
}
