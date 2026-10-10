import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import css from "./WeeklyLeaderboardBox.module.css"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import UserBadge from "../UserBadge/UserBadge"

const PERIODS = {
  weekly: { label: "Settimana", title: "Top 5 della settimana", empty: "Nessun risultato per questa settimana." },
  monthly: { label: "Mese", title: "Top 5 del mese", empty: "Nessun risultato per questo mese." },
}

function WeeklyLeaderboardBox() {
  const [period, setPeriod] = useState("weekly")
  const [tops, setTops] = useState({ weekly: [], monthly: [] })

  // Entrambe le classifiche vengono caricate subito, cosi' il toggle e' istantaneo.
  useEffect(() => {
    Object.keys(PERIODS).forEach((key) => {
      api.get(`/leaderboard/${key}`)
        .then((res) => setTops((prev) => ({ ...prev, [key]: (res.data.results || []).slice(0, 5) })))
        .catch((err) => logError(err))
    })
  }, [])

  const current = PERIODS[period]
  const top = tops[period]

  return (
    <div className={css.leaderboardBox}>
      <div className={css.leaderboardBoxHeader}>
        <div className={css.headerTop}>
          <span className={css.sectionBadge}>Classifiche</span>

          <div className={css.toggle} role="tablist" aria-label="Periodo classifica">
            {Object.entries(PERIODS).map(([key, p]) => (
              <button
                key={key}
                type="button"
                role="tab"
                aria-selected={period === key}
                className={`${css.toggleBtn} ${period === key ? css.toggleBtnActive : ""}`}
                onClick={() => setPeriod(key)}
              >
                {p.label}
              </button>
            ))}
          </div>
        </div>
        <h2 className={css.leaderboardBoxTitle}>{current.title}</h2>
      </div>

      {top.length > 0 ? (
        <ol className={css.leaderboardList}>
          {top.map((r) => (
            <li key={r.position} className={css.leaderboardRow}>
              <span className={css.leaderboardRank}>#{r.position}</span>
              <span className={css.leaderboardName}>{r.nickname}</span>
              <UserBadge badges={r.badges} />
              <span className={css.leaderboardScore}>{formatQuizScore(r.total_score)}</span>
            </li>
          ))}
        </ol>
      ) : (
        <div className={css.emptyBox}>{current.empty}</div>
      )}

      <div className={css.leaderboardLinks}>
        <Link to={`/classifiche?tab=${period}`} className={css.seeMoreLink}>
          Classifica completa
          <i className="bi bi-arrow-right"></i>
        </Link>
      </div>
    </div>
  )
}

export default WeeklyLeaderboardBox
