import styles from "./UserBadge.module.css"

function UserBadge({ label }) {
  if (!label) return null

  return (
    <span className={styles.badge} title={label}>
      <i className="bi bi-award-fill"></i>
      {label}
    </span>
  )
}

export default UserBadge
