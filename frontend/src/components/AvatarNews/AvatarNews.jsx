import { useEffect, useRef, useState } from "react"
import { useLocation, useNavigate } from "react-router-dom"
import api from "../../service/api"
import { useAuth } from "../../context/useAuth"
import { RARITY } from "../Avatar3D/catalog"
import css from "./AvatarNews.module.css"

/**
 * Avviso globale "Hai sbloccato nuovi oggetti!": si controlla all'accesso e
 * ogni volta che cambia il livello (fine partita, bonus giornaliero, ...).
 */
export default function AvatarNews() {
  const { user, token, refreshUser } = useAuth()
  const location = useLocation()
  const navigate = useNavigate()
  const [items, setItems] = useState([])
  const prevLevel = useRef(null)

  const level = user?.xp_info?.level
  const onAvatarPage = location.pathname.startsWith("/avatar")

  useEffect(() => {
    if (!token || !user?.id || onAvatarPage) return
    let alive = true

    api
      .get("/avatar/news")
      .then((res) => {
        if (alive) setItems(res.data.items || [])
      })
      .catch(() => {})

    // Salendo di livello arrivano anche monete: aggiorna il saldo in navbar.
    if (prevLevel.current != null && level !== prevLevel.current) refreshUser?.()
    prevLevel.current = level

    return () => {
      alive = false
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token, user?.id, level])

  if (!items.length || onAvatarPage) return null

  const close = () => {
    setItems([])
    api.post("/avatar/news/seen").catch(() => {})
  }

  const open = () => {
    setItems([])
    navigate("/avatar")
  }

  const shown = items.slice(0, 6)

  return (
    <div className={css.backdrop} role="dialog" aria-modal="true" aria-labelledby="avatar-news-title">
      <div className={css.box}>
        <div className={css.gift}>
          <i className="bi bi-gift-fill"></i>
        </div>
        <h2 id="avatar-news-title" className={css.title}>
          {items.length === 1 ? "Nuovo oggetto sbloccato!" : `Hai sbloccato ${items.length} nuovi oggetti!`}
        </h2>
        <p className={css.sub}>Salendo di livello hai ricevuto nuovi oggetti per il tuo avatar.</p>

        <ul className={css.list}>
          {shown.map((it) => (
            <li key={it.key} style={{ "--rarity": (RARITY[it.rarity] || RARITY.comune).color }}>
              <span className={css.rarity}>{(RARITY[it.rarity] || RARITY.comune).label}</span>
              <strong>{it.name}</strong>
              {it.unlock_level && <span className={css.level}>Lv {it.unlock_level}</span>}
            </li>
          ))}
        </ul>
        {items.length > shown.length && <p className={css.more}>... e altri {items.length - shown.length}</p>}

        <div className={css.actions}>
          <button type="button" className={css.primary} onClick={open}>
            Indossali ora
          </button>
          <button type="button" className={css.secondary} onClick={close}>
            Più tardi
          </button>
        </div>
      </div>
    </div>
  )
}
