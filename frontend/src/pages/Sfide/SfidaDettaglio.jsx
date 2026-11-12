import { useEffect, useState } from "react"
import { Link, useNavigate, useParams } from "react-router-dom"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import XpGain from "../../components/XpGain/XpGain"
import { notifyChallengesChanged } from "../../hooks/useChallengeCount"
import QuestionCategory from "./QuestionCategory"
import styles from "./Sfide.module.css"
import UserAvatar from "../../components/UserAvatar/UserAvatar"
import UserTitle from "../../components/UserAvatar/UserTitle"
import GuestShare from "./GuestShare"

const CLOSED_LABEL = {
  expired: "Sfida scaduta: nessuno può più giocarla.",
  declined: "Sfida rifiutata da tutti.",
  cancelled: "Sfida annullata.",
}

const STATUS_TAG = {
  not_sent: { label: "Da invitare", cls: styles.statusWarn },
  invited: { label: "Invitato", cls: "" },
  accepted: { label: "In gioco", cls: styles.statusOk },
  declined: { label: "Ha rifiutato", cls: styles.statusOut },
  missed: { label: "Non ha giocato", cls: styles.statusOut },
}

function formatDeadline(iso) {
  return new Date(iso).toLocaleString("it-IT", {
    weekday: "short",
    day: "numeric",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  })
}

function resultBanner(challenge) {
  if (challenge.status !== "completed") return CLOSED_LABEL[challenge.status] || null
  if (challenge.result === "won") return "Hai vinto! 🏆"
  if (challenge.result === "draw") return "Pareggio al primo posto"
  return `Sei arrivato ${challenge.my_rank}° su ${challenge.players.filter((p) => p.rank).length}`
}

function Review({ review }) {
  const nicknames = Object.fromEntries(review.players.map((p) => [p.id, p.is_me ? "Tu" : p.nickname]))

  return (
    <div className="mt-4">
      <h2 className={styles.sectionTitle}>Domande e risposte</h2>

      {review.questions.map((q, index) => (
        <div key={q.id} className={styles.reviewItem}>
          <div className={styles.reviewQuestion}>
            {index + 1}. {q.question_text}
          </div>

          <QuestionCategory category={q.category} subcategory={q.subcategory} training={q.training} />

          {q.image && <img src={q.image} alt="Domanda" className="img-fluid mb-2" style={{ maxHeight: 180 }} />}

          {q.answers.map((a) => {
            const pickers = q.results.filter((r) => r.answer_id === a.id)
            const cls = a.is_correct ? styles.reviewCorrect : pickers.length ? styles.reviewWrongPick : ""

            return (
              <div key={a.id} className={`${styles.reviewAnswer} ${cls}`}>
                <span>
                  {a.is_correct && <i className="bi bi-check-circle-fill text-success me-1"></i>}
                  {a.answer_text}
                </span>
                <span className="d-flex flex-wrap gap-1">
                  {pickers.map((r) => (
                    <span key={r.user_id} className={styles.tag}>
                      {nicknames[r.user_id]}
                    </span>
                  ))}
                </span>
              </div>
            )
          })}

          {q.results.some((r) => r.is_timeout) && (
            <div className={`${styles.meta} mt-1`}>
              Tempo scaduto:{" "}
              {q.results
                .filter((r) => r.is_timeout)
                .map((r) => nicknames[r.user_id])
                .join(", ")}
            </div>
          )}
        </div>
      ))}
    </div>
  )
}

function PlayerRow({ player, closed }) {
  const tag = STATUS_TAG[player.status]
  const showTag = player.status === "declined" || player.status === "missed" || (!closed && !player.completed)

  return (
    <div className={`${styles.playerRow} ${player.is_me ? styles.playerRowMe : ""}`}>
      <span className="d-flex align-items-center gap-2">
        {closed && player.rank ? (
          <span className={`${styles.rankBadge} ${player.rank === 1 ? styles.rankFirst : ""}`}>{player.rank}</span>
        ) : null}
        <UserAvatar url={player.avatar_url} frame={player.avatar_frame} name={player.nickname} size={34} />
        <span>
          {player.is_me ? "Tu" : player.nickname} <UserTitle title={player.avatar_title} />{" "}
          {player.is_guest ? <span className={styles.guestTag}>non ancora iscritto</span> : <span className={styles.meta}>Lv {player.level}</span>}
          {player.is_challenger && <span className={styles.meta}> · ha lanciato la sfida</span>}
        </span>
      </span>

      <span className="d-flex align-items-center gap-2">
        {closed && player.score !== undefined && <strong>{formatQuizScore(player.score)}</strong>}
        {!closed && player.completed && <span className={`${styles.statusTag} ${styles.statusOk}`}>Ha finito</span>}
        {!closed && !player.completed && player.started && <span className={styles.statusTag}>Sta giocando</span>}
        {showTag && tag && !(!closed && player.started) && (
          <span className={`${styles.statusTag} ${tag.cls}`}>{tag.label}</span>
        )}
      </span>
    </div>
  )
}

function SfidaDettaglio() {
  const { id } = useParams()
  const navigate = useNavigate()

  const [challenge, setChallenge] = useState(null)
  const [review, setReview] = useState(null)
  const [error, setError] = useState("")
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    let cancelled = false

    async function load() {
      try {
        const res = await api.get(`/challenges/${id}`)
        if (cancelled) return

        setChallenge(res.data)

        if (res.data.status !== "open") {
          const rev = await api.get(`/challenges/${id}/review`)
          if (!cancelled) setReview(rev.data)
        }
      } catch (err) {
        logError(err)
        if (!cancelled) setError(err.response?.data?.message || "Sfida non trovata")
      }
    }

    load()

    return () => {
      cancelled = true
    }
  }, [id])

  async function giveUp() {
    setBusy(true)

    try {
      await api.post(`/challenges/${id}/decline`)
      notifyChallengesChanged()
      navigate("/sfide", { replace: true })
    } catch (err) {
      logError(err)
      setError(err.response?.data?.message || "Operazione non riuscita")
      setBusy(false)
    }
  }

  if (error) {
    return (
      <div className={`container ${styles.page}`}>
        <div className="alert alert-danger">{error}</div>
        <Link to="/sfide" className="btn btn-outline-secondary">
          Torna alle sfide
        </Link>
      </div>
    )
  }

  if (!challenge) {
    return (
      <div className={`container ${styles.page}`}>
        <div className="spinner-border text-primary"></div>
      </div>
    )
  }

  const { status, me } = challenge
  const closed = status !== "open"
  const others = challenge.players.filter((p) => !p.is_me)
  const waitingFor = others.filter((p) => ["invited", "accepted"].includes(p.status) && !p.completed)
  const banner = resultBanner(challenge)

  return (
    <div className={`container ${styles.page}`}>
      <div className={`${styles.panel} ${styles.panelWide}`}>
        <div className={styles.meta}>
          {challenge.categories.join(", ")} · {challenge.mode === "live" ? "Live" : "Differita"} ·{" "}
          {challenge.players.length} giocatori
        </div>

        {banner && <div className={styles.resultBanner}>{banner}</div>}

        <div className="my-3">
          {challenge.players.map((p) => (
            <PlayerRow key={p.id} player={p} closed={closed} />
          ))}
        </div>

        {challenge.guest_url && status === "open" && (
          <GuestShare
            challengeId={challenge.id}
            url={challenge.guest_url}
            guestPlayed={others.some((p) => p.is_guest && p.completed)}
            invited={challenge.guest_invited}
            onInvited={() => setChallenge((c) => ({ ...c, guest_invited: true, players: c.players.map((pl) => (pl.is_guest ? { ...pl, status: "invited" } : pl)) }))}
          />
        )}

        {status === "completed" && <XpGain awarded={challenge.xp_awarded} xp={challenge.xp} />}

        {status === "open" && (
          <div className="text-center">
            {!me.completed && challenge.mode === "live" ? (
              <>
                <p className={styles.meta}>
                  Sfida live: si gioca in diretta con gli altri giocatori, con le domande in contemporanea.
                </p>
                <Link to={`/sfide/${id}/live`} className="btn btn-warning btn-lg fw-bold">
                  <i className="bi bi-lightning-charge-fill"></i> Entra nella sala live
                </Link>
              </>
            ) : !me.completed ? (
              <>
                <p className={styles.meta}>
                  7 domande a tempo, più sei veloce più punti fai. Le risposte giuste si vedranno a sfida conclusa.
                  Scade {formatDeadline(challenge.expires_at)}.
                </p>
                <Link to={`/sfide/${id}/gioca`} className="btn btn-warning btn-lg fw-bold">
                  {challenge.my_started ? "Riprendi la sfida" : "Gioca ora"}
                </Link>
              </>
            ) : (
              <p className="fw-bold">
                Hai finito! In attesa di {waitingFor.map((p) => p.nickname).join(", ") || "gli altri giocatori"}:
                risultati e risposte appariranno a sfida conclusa (entro {formatDeadline(challenge.expires_at)}).
              </p>
            )}

            {challenge.can_give_up && (
              <div className="mt-3">
                <button className="btn btn-outline-danger btn-sm" disabled={busy} onClick={giveUp}>
                  {challenge.direction === "sent" ? "Annulla sfida" : "Rifiuta sfida"}
                </button>
              </div>
            )}
          </div>
        )}

        <div className="text-center mt-3">
          <Link to="/sfide" className="btn btn-outline-secondary btn-sm">
            Tutte le sfide
          </Link>
        </div>

        {review && review.questions.length > 0 && <Review review={review} />}
      </div>
    </div>
  )
}

export default SfidaDettaglio
