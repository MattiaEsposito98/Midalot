import css from "./UserAvatar.module.css"

const COLORS = ["#6c4bd8", "#2f6fd0", "#2f9b5a", "#e23b3b", "#f08a24", "#e05fb0", "#1d8f9c", "#8a5a34"]

function colorFor(name = "") {
  let h = 0
  for (const ch of name) h = (h * 31 + ch.charCodeAt(0)) >>> 0
  return COLORS[h % COLORS.length]
}

/**
 * Ritratto dell'avatar di un utente (immagine generata quando salva l'avatar).
 * Chi non l'ha ancora creato vede la sua iniziale su un cerchio colorato.
 */
function UserAvatar({ url, name, size = 32, className = "" }) {
  const style = { width: size, height: size, fontSize: Math.round(size * 0.45) }

  if (url) {
    return <img src={url} alt="" className={`${css.avatar} ${className}`} style={style} loading="lazy" />
  }

  return (
    <span className={`${css.avatar} ${css.fallback} ${className}`} style={{ ...style, background: colorFor(name) }} aria-hidden="true">
      {(name || "?").trim().charAt(0).toUpperCase()}
    </span>
  )
}

export default UserAvatar
