import css from "./UserAvatar.module.css"

// Titolo dell'avatar (es. "Leggenda di Midalot") mostrato accanto al nickname.
function UserTitle({ title, className = "" }) {
  if (!title) return null
  return <span className={`${css.title} ${className}`}>{title}</span>
}

export default UserTitle
