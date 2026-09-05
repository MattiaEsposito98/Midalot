import { useEffect, useMemo, useState } from "react"
import { useSearchParams } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import styles from "./Classifiche.module.css"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import UserBadge from "../../components/UserBadge/UserBadge"

const MONTH_NAMES = [
  "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
  "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre",
]

function formatLocalDate(d) {
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, "0")
  const day = String(d.getDate()).padStart(2, "0")
  return `${year}-${month}-${day}`
}

function getCurrentWeekStart() {
  const d = new Date()
  const day = d.getDay()
  const diff = day === 0 ? -6 : 1 - day
  d.setDate(d.getDate() + diff)
  return formatLocalDate(d)
}

function getCurrentMonth() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`
}

function formatWeekLabel(weekStart) {
  const start = new Date(`${weekStart}T00:00:00`)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)

  const fmt = (d) => `${d.getDate()} ${MONTH_NAMES[d.getMonth()].slice(0, 3)}`

  return `${fmt(start)} - ${fmt(end)} ${end.getFullYear()}`
}

function formatMonthLabel(month) {
  const [year, monthNum] = month.split("-")
  return `${MONTH_NAMES[Number(monthNum) - 1]} ${year}`
}

function Classifiche() {
  const { user } = useAuth()
  const [searchParams, setSearchParams] = useSearchParams()
  const initialTab = searchParams.get("tab") === "monthly" ? "monthly" : "weekly"

  const [tab, setTab] = useState(initialTab)
  const [weeks, setWeeks] = useState([])
  const [months, setMonths] = useState([])
  const [selectedWeek, setSelectedWeek] = useState(getCurrentWeekStart())
  const [selectedMonth, setSelectedMonth] = useState(getCurrentMonth())
  const [weeklyData, setWeeklyData] = useState(null)
  const [monthlyData, setMonthlyData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    Promise.all([api.get("/leaderboard/weeks"), api.get("/leaderboard/months")])
      .then(([weeksRes, monthsRes]) => {
        const currentWeek = getCurrentWeekStart()
        const currentMonth = getCurrentMonth()

        const weekList = Array.from(new Set([currentWeek, ...(weeksRes.data.weeks || [])]))
          .sort((a, b) => (a < b ? 1 : -1))

        const monthList = Array.from(new Set([currentMonth, ...(monthsRes.data.months || [])]))
          .sort((a, b) => (a < b ? 1 : -1))

        setWeeks(weekList)
        setMonths(monthList)
      })
      .catch((err) => logError(err))
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    api.get("/leaderboard/weekly", { params: { week: selectedWeek } })
      .then((res) => setWeeklyData(res.data))
      .catch((err) => {
        logError(err)
        setError("Errore nel caricamento della classifica settimanale")
      })
  }, [selectedWeek])

  useEffect(() => {
    api.get("/leaderboard/monthly", { params: { month: selectedMonth } })
      .then((res) => setMonthlyData(res.data))
      .catch((err) => {
        logError(err)
        setError("Errore nel caricamento della classifica mensile")
      })
  }, [selectedMonth])

  function handleTabChange(newTab) {
    setTab(newTab)
    setSearchParams({ tab: newTab })
  }

  const data = tab === "weekly" ? weeklyData : monthlyData
  const results = data?.results || []

  const periodLabel = useMemo(() => {
    if (tab === "weekly") return formatWeekLabel(selectedWeek)
    return formatMonthLabel(selectedMonth)
  }, [tab, selectedWeek, selectedMonth])

  if (loading) {
    return (
      <div className={`container ${styles.loadingWrap}`}>
        <div className="spinner-border text-primary"></div>
        <p className={styles.loadingText}>Caricamento classifiche...</p>
      </div>
    )
  }

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <div>
          <span className={styles.eyebrow}>
            <i className="bi bi-trophy-fill"></i>
            Classifica premi
          </span>
          <h1 className={styles.title}>Classifiche</h1>
          <p className={styles.subtitle}>
            Punteggio totale ottenuto nel periodo selezionato.
          </p>
        </div>
      </div>

      <div className={styles.infoNote}>
        <i className="bi bi-info-circle"></i>
        I punti derivano da Quiz One Shot e Minigiochi. Il Training ha una classifica propria per categoria e non contribuisce a questa classifica.
      </div>

      <div className={styles.tabs}>
        <button
          type="button"
          className={`${styles.tabBtn} ${tab === "weekly" ? styles.tabActive : ""}`}
          onClick={() => handleTabChange("weekly")}
        >
          Settimanale
        </button>
        <button
          type="button"
          className={`${styles.tabBtn} ${tab === "monthly" ? styles.tabActive : ""}`}
          onClick={() => handleTabChange("monthly")}
        >
          Mensile
        </button>
      </div>

      <div className={styles.periodBar}>
        {tab === "weekly" ? (
          <select
            className="form-select"
            value={selectedWeek}
            onChange={(e) => setSelectedWeek(e.target.value)}
          >
            {weeks.map((week) => (
              <option key={week} value={week}>
                {formatWeekLabel(week)}
              </option>
            ))}
          </select>
        ) : (
          <select
            className="form-select"
            value={selectedMonth}
            onChange={(e) => setSelectedMonth(e.target.value)}
          >
            {months.map((month) => (
              <option key={month} value={month}>
                {formatMonthLabel(month)}
              </option>
            ))}
          </select>
        )}
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      <p className={styles.periodLabel}>{periodLabel}</p>

      {results.length === 0 && (
        <div className={`alert alert-info ${styles.emptyBox}`}>
          Nessun risultato per questo periodo.
        </div>
      )}

      <div className={styles.list}>
        {results.map((r) => {
          const isMe = user?.nickname === r.nickname
          const isTop3 = r.position <= 3

          return (
            <div
              key={`${r.position}-${r.nickname}`}
              className={`${styles.row} ${isMe ? styles.meRow : ""} ${isTop3 ? styles.topRow : ""}`}
            >
              <div className={styles.rankCol}>
                <div className={`${styles.rankBadge} ${isTop3 ? styles.topBadge : ""}`}>
                  #{r.position}
                </div>
              </div>

              <div className={styles.mainCol}>
                <div className={styles.nameWrap}>
                  <span className={styles.name}>{r.nickname}</span>
                  {isMe && <span className={styles.meTag}>Tu</span>}
                  <UserBadge label={r.badge} />
                </div>

                <div className={styles.statsGrid}>
                  <div className={styles.statBox}>
                    <span className={styles.statLabel}>Punteggio totale</span>
                    <strong className={styles.statValue}>{formatQuizScore(r.total_score)}</strong>
                  </div>

                  <div className={styles.statBox}>
                    <span className={styles.statLabel}>Attività completate</span>
                    <strong className={styles.statValue}>{r.quizzes_completed}</strong>
                  </div>
                </div>
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}

export default Classifiche
