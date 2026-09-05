import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import css from "./WeeklyLeaderboardBox.module.css"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import UserBadge from "../UserBadge/UserBadge"

function WeeklyLeaderboardBox() {
  const [weeklyTop, setWeeklyTop] = useState([])

  useEffect(() => {
    api.get("/leaderboard/weekly")
      .then((res) => setWeeklyTop((res.data.results || []).slice(0, 5)))
      .catch((err) => logError(err))
  }, [])

  return (
    <div className={css.leaderboardBox}>
      <div className={css.leaderboardBoxHeader}>
        <span className={css.sectionBadge}>Classifiche</span>
        <h2 className={css.leaderboardBoxTitle}>Top 5 della settimana</h2>
      </div>

      {weeklyTop.length > 0 ? (
        <ol className={css.leaderboardList}>
          {weeklyTop.map((r) => (
            <li key={r.position} className={css.leaderboardRow}>
              <span className={css.leaderboardRank}>#{r.position}</span>
              <span className={css.leaderboardName}>{r.nickname}</span>
              <UserBadge label={r.badge} />
              <span className={css.leaderboardScore}>{formatQuizScore(r.total_score)}</span>
            </li>
          ))}
        </ol>
      ) : (
        <div className={css.emptyBox}>Nessun risultato per questa settimana.</div>
      )}

      <div className={css.leaderboardLinks}>
        <Link to="/classifiche?tab=weekly" className={css.seeMoreLink}>
          Settimanale
          <i className="bi bi-arrow-right"></i>
        </Link>
        <Link to="/classifiche?tab=monthly" className={css.seeMoreLink}>
          Mensile
          <i className="bi bi-arrow-right"></i>
        </Link>
      </div>
    </div>
  )
}

export default WeeklyLeaderboardBox
