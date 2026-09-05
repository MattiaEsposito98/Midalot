import styles from "./ComingSoon.module.css"

function ComingSoon({
  icon = "bi-stars",
  title = "Presto in arrivo!",
  message = "Stiamo preparando nuovi contenuti per te. Torna a trovarci presto!",
  compact = false,
}) {
  return (
    <div className={`${styles.wrap} ${compact ? styles.compact : ""}`}>
      <div className={styles.glow}></div>
      <div className={styles.iconWrap}>
        <i className={`bi ${icon}`}></i>
      </div>
      <h3 className={styles.title}>{title}</h3>
      <p className={styles.message}>{message}</p>
    </div>
  )
}

export default ComingSoon
