import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import api from "../../service/api"
import { useAuth } from "../../context/useAuth"
import { logError } from "../../utils/logger"
import styles from "./Sfide.module.css"
import UserAvatar from "../../components/UserAvatar/UserAvatar"

const RESULT_LABEL = { won: "Vinta", lost: "Persa", draw: "Pareggio" }
const STATUS_LABEL = {
  expired: "Scaduta",
  declined: "Rifiutata",
  cancelled: "Annullata",
}

function formatDeadline(iso) {
  return new Date(iso).toLocaleString("it-IT", {
    day: "numeric",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  })
}

function ChallengeCard({ challenge }) {
  const { challenger, others, direction, my_turn: myTurn, result, status, mode } = challenge
  const live = mode === "live" && status === "open"

  return (
    <div className={styles.card}>
      <div className={styles.cardMain}>
        <div className={styles.versus}>
          <span className={styles.avatarStack}>
            {(direction === "sent" ? others : [challenger]).slice(0, 4).map((p) => (
              <UserAvatar key={p.id} url={p.avatar_url} name={p.nickname} size={30} />
            ))}
          </span>{" "}
          {direction === "sent"
            ? `Sfida a ${others.map((o) => o.nickname).join(", ")}`
            : `Sfida da ${challenger.nickname}${others.length > 1 ? ` + ${others.length - 1} altri` : ""}`}
          {others.length > 1 && direction === "sent" && <span className={styles.meta}> ({others.length + 1} giocatori)</span>}
        </div>
        <div className={styles.meta}>
          {challenge.categories.join(", ")}
          {status === "open" && <> · scade {formatDeadline(challenge.expires_at)}</>}
        </div>
      </div>

      <div className="d-flex align-items-center gap-2">
        {live && <span className={`${styles.pill} ${styles.livePill}`}>LIVE</span>}
        {result && (
          <span className={`${styles.pill} ${styles[result]}`}>{RESULT_LABEL[result]}</span>
        )}
        {STATUS_LABEL[status] && <span className={styles.pill}>{STATUS_LABEL[status]}</span>}
        <Link
          className={`btn btn-sm ${myTurn ? "btn-warning fw-bold" : "btn-outline-dark"}`}
          to={live && myTurn ? `/sfide/${challenge.id}/live` : `/sfide/${challenge.id}`}
        >
          {myTurn ? (live ? "Entra" : "Gioca") : "Apri"}
        </Link>
      </div>
    </div>
  )
}

function Section({ title, items, emptyText }) {
  return (
    <section className={styles.section}>
      <h2 className={styles.sectionTitle}>{title}</h2>
      {items.length === 0 ? (
        <div className={styles.empty}>{emptyText}</div>
      ) : (
        items.map((challenge) => <ChallengeCard key={challenge.id} challenge={challenge} />)
      )}
    </section>
  )
}

function Sfide() {
  const { user } = useAuth()
  const [data, setData] = useState(null)
  const [error, setError] = useState("")
  const [accepts, setAccepts] = useState(user?.accepts_challenges !== false)

  useEffect(() => {
    api
      .get("/challenges")
      .then((res) => setData(res.data))
      .catch((err) => {
        logError(err)
        setError("Impossibile caricare le sfide")
      })
  }, [])

  async function toggleAccepts() {
    const next = !accepts
    setAccepts(next)

    try {
      await api.put("/challenges/settings", { accepts_challenges: next })
    } catch (err) {
      logError(err)
      setAccepts(!next)
    }
  }

  return (
    <div className={`container ${styles.page}`}>
      <div className={styles.header}>
        <div>
          <h1 className={styles.title}>Sfide</h1>
          <p className={styles.subtitle}>
            Sfida un altro giocatore su 7 domande dei Training: vince chi fa più punti.
          </p>
        </div>
        <Link to="/sfide/nuova" className="btn btn-warning fw-bold">
          <i className="bi bi-plus-lg"></i> Nuova sfida
        </Link>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {!data && !error && <div className="spinner-border text-primary"></div>}

      {data && (
        <>
          <Section title="Tocca a te" items={data.to_play} emptyText="Nessuna sfida da giocare." />
          <Section title="In attesa dell'avversario" items={data.waiting} emptyText="Nessuna sfida in attesa." />
          <Section title="Concluse" items={data.history} emptyText="Non hai ancora sfide concluse." />
        </>
      )}

      <div className="form-check form-switch mt-4">
        <input
          className="form-check-input"
          type="checkbox"
          id="acceptsChallenges"
          checked={accepts}
          onChange={toggleAccepts}
        />
        <label className="form-check-label" htmlFor="acceptsChallenges">
          Accetto di essere sfidato da altri giocatori
        </label>
      </div>
    </div>
  )
}

export default Sfide
