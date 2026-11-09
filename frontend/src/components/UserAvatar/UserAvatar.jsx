import css from "./UserAvatar.module.css"

const COLORS = ["#6c4bd8", "#2f6fd0", "#2f9b5a", "#e23b3b", "#f08a24", "#e05fb0", "#1d8f9c", "#8a5a34"]

function colorFor(name = "") {
  let h = 0
  for (const ch of name) h = (h * 31 + ch.charCodeAt(0)) >>> 0
  return COLORS[h % COLORS.length]
}

/**
 * Ritratto dell'avatar di un utente (immagine generata quando salva l'avatar),
 * con la cornice scelta. Chi non l'ha ancora creato vede la sua iniziale.
 */
function UserAvatar({ url, name, size = 32, frame, className = "" }) {
  const framed = !!frame
  const pad = framed ? Math.max(2, Math.round(size * 0.09)) : 0
  const style = { width: size, height: size, fontSize: Math.round(size * 0.45) }
  const innerClass = `${css.avatar} ${framed ? css.inFrame : className}`

  const inner = url ? (
    <img src={url} alt="" className={innerClass} style={style} loading="lazy" />
  ) : (
    <span className={`${innerClass} ${css.fallback}`} style={{ ...style, background: colorFor(name) }} aria-hidden="true">
      {(name || "?").trim().charAt(0).toUpperCase()}
    </span>
  )

  if (!framed) return inner

  return (
    <span className={`${css.frame} ${className}`} data-frame={frame} style={{ padding: pad, "--s": `${size}px` }}>
      {inner}
    </span>
  )
}

export default UserAvatar
