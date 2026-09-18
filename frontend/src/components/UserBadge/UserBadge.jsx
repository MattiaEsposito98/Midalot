import styles from "./UserBadge.module.css"

const ICON_BY_TYPE = {
  monthly: "bi-award-fill",
  midalario: "bi-broadcast",
}

const CLASS_BY_TYPE = {
  monthly: styles.badgeMonthly,
  midalario: styles.badgeMidalario,
}

function UserBadge({ badges }) {
  const list = (badges || []).filter((badge) => badge && badge.label)

  if (list.length === 0) return null

  return (
    <span className={styles.badges}>
      {list.map((badge, index) => (
        <span
          key={`${badge.type}-${index}`}
          className={`${styles.badge} ${CLASS_BY_TYPE[badge.type] || styles.badgeMonthly}`}
          tabIndex={0}
          aria-label={badge.label}
        >
          <i className={`bi ${ICON_BY_TYPE[badge.type] || "bi-award-fill"}`}></i>
          <span className={styles.tooltip} role="tooltip">
            {badge.label}
          </span>
        </span>
      ))}
    </span>
  )
}

export default UserBadge
