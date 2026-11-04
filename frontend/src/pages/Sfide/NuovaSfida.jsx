import { useEffect, useRef, useState } from "react"
import { Link, useNavigate } from "react-router-dom"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { notifyChallengesChanged } from "../../hooks/useChallengeCount"
import styles from "./Sfide.module.css"

function NuovaSfida() {
  const navigate = useNavigate()

  const [query, setQuery] = useState("")
  const [results, setResults] = useState([])
  const [searchedTerm, setSearchedTerm] = useState("")
  const [opponents, setOpponents] = useState([])
  const [maxInvitees, setMaxInvitees] = useState(4)
  const [categories, setCategories] = useState([])
  const [allCount, setAllCount] = useState(0)
  const [selected, setSelected] = useState([])
  const [mode, setMode] = useState("async")
  const [sending, setSending] = useState(false)
  const [error, setError] = useState("")
  const searchSeq = useRef(0)

  useEffect(() => {
    api
      .get("/challenges/categories")
      .then((res) => {
        setCategories(res.data.categories)
        setAllCount(res.data.all_questions_count)
        setMaxInvitees(res.data.max_invitees)
      })
      .catch(logError)
  }, [])

  const full = opponents.length >= maxInvitees

  useEffect(() => {
    const term = query.trim()
    const seq = ++searchSeq.current

    if (term.length < 2 || full) return undefined

    const timer = setTimeout(async () => {
      try {
        const res = await api.get("/challenges/users", { params: { q: term } })
        if (seq === searchSeq.current) {
          setResults(res.data.users)
          setSearchedTerm(term)
        }
      } catch (err) {
        logError(err)
      }
    }, 300)

    return () => clearTimeout(timer)
  }, [query, full])

  function addOpponent(user) {
    setOpponents((prev) => (prev.some((o) => o.id === user.id) || prev.length >= maxInvitees ? prev : [...prev, user]))
    setQuery("")
    setResults([])
  }

  function removeOpponent(id) {
    setOpponents((prev) => prev.filter((o) => o.id !== id))
  }

  function toggleCategory(id) {
    setSelected((prev) => (prev.includes(id) ? prev.filter((c) => c !== id) : [...prev, id]))
  }

  const availableQuestions = selected.length
    ? categories.filter((c) => selected.includes(c.id)).reduce((sum, c) => sum + c.questions_count, 0)
    : allCount

  const visibleResults = results.filter((u) => !opponents.some((o) => o.id === u.id))

  async function submit() {
    if (opponents.length === 0 || sending) return

    setSending(true)
    setError("")

    try {
      const res = await api.post("/challenges", {
        opponent_ids: opponents.map((o) => o.id),
        category_ids: selected.length ? selected : null,
        mode,
      })
      notifyChallengesChanged()
      navigate(mode === "live" ? `/sfide/${res.data.id}/live` : `/sfide/${res.data.id}`, { replace: true })
    } catch (err) {
      logError(err)
      setError(err.response?.data?.message || "Impossibile creare la sfida")
    } finally {
      setSending(false)
    }
  }

  return (
    <div className={`container ${styles.page}`}>
      <div className={`${styles.panel} ${styles.panelWide}`}>
        <h1 className={styles.title}>Nuova sfida</h1>
        <p className={styles.subtitle}>
          Sfida fino a {maxInvitees} giocatori (tu compreso siete al massimo {maxInvitees + 1}): le 7 domande vengono
          estratte dai Training.
        </p>

        <h2 className={`${styles.sectionTitle} mt-4`}>
          1. Avversari{" "}
          <span className={styles.meta}>
            ({opponents.length}/{maxInvitees})
          </span>
        </h2>

        {opponents.length > 0 && (
          <div className="d-flex flex-wrap gap-2 mb-2">
            {opponents.map((o) => (
              <span key={o.id} className={styles.opponentChip}>
                {o.nickname} <span className={styles.meta}>Lv {o.level}</span>
                <button
                  type="button"
                  className={styles.chipRemove}
                  aria-label={`Rimuovi ${o.nickname}`}
                  onClick={() => removeOpponent(o.id)}
                >
                  <i className="bi bi-x-lg"></i>
                </button>
              </span>
            ))}
          </div>
        )}

        {full ? (
          <div className={styles.meta}>Hai raggiunto il massimo di {maxInvitees} avversari.</div>
        ) : (
          <>
            <input
              className="form-control"
              placeholder={opponents.length ? "Aggiungi un altro avversario" : "Cerca per nickname"}
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              maxLength={50}
            />
            <div className="mt-2">
              {visibleResults.map((u) => (
                <button key={u.id} type="button" className={styles.userResult} onClick={() => addOpponent(u)}>
                  <span>{u.nickname}</span>
                  <span className={styles.meta}>Lv {u.level}</span>
                </button>
              ))}
              {query.trim().length >= 2 && searchedTerm === query.trim() && visibleResults.length === 0 && (
                <div className={styles.meta}>Nessun giocatore trovato.</div>
              )}
            </div>
          </>
        )}

        <h2 className={`${styles.sectionTitle} mt-4`}>2. Categorie</h2>
        <div className={`${styles.hint} ${selected.length === 0 ? styles.hintAll : ""}`}>
          <i className={`bi ${selected.length === 0 ? "bi-check2-circle" : "bi-funnel-fill"}`}></i>
          <span>
            {selected.length === 0 ? (
              <>
                <strong>Tutte le categorie.</strong> Non hai selezionato nulla, quindi le domande saranno pescate da
                tutti i Training. Tocca una o più categorie per restringere la scelta.
              </>
            ) : (
              <>
                <strong>
                  {selected.length === 1 ? "1 categoria selezionata." : `${selected.length} categorie selezionate.`}
                </strong>{" "}
                Le domande saranno pescate solo da {selected.length === 1 ? "questa" : "queste"}. Tocca di nuovo per
                deselezionare.
              </>
            )}
          </span>
        </div>

        <div className={styles.categoryGrid}>
          {categories.map((c) => (
            <button
              key={c.id}
              type="button"
              className={`${styles.categoryChip} ${selected.includes(c.id) ? styles.categoryChipActive : ""}`}
              onClick={() => toggleCategory(c.id)}
            >
              {c.name}
              <span className={styles.meta}> · {c.questions_count} domande</span>
            </button>
          ))}
        </div>

        <div className={`${styles.meta} mt-2`}>Domande disponibili: {availableQuestions}</div>

        <h2 className={`${styles.sectionTitle} mt-4`}>3. Modalità</h2>

        <div className={styles.categoryGrid}>
          <button
            type="button"
            className={`${styles.categoryChip} ${mode === "async" ? styles.categoryChipActive : ""}`}
            onClick={() => setMode("async")}
          >
            <i className="bi bi-clock-history"></i> Differita
            <span className={styles.meta}> · ognuno gioca quando può, entro 72 ore</span>
          </button>
          <button
            type="button"
            className={`${styles.categoryChip} ${mode === "live" ? styles.categoryChipActive : ""}`}
            onClick={() => setMode("live")}
          >
            <i className="bi bi-lightning-charge-fill"></i> Live
            <span className={styles.meta}> · in diretta, gli invitati devono accettare entro 10 minuti</span>
          </button>
        </div>

        {error && <div className="alert alert-danger mt-3">{error}</div>}

        <div className="d-flex gap-2 mt-4">
          <button
            className="btn btn-warning fw-bold"
            disabled={opponents.length === 0 || sending || availableQuestions < 7}
            onClick={submit}
          >
            {sending
              ? "Invio..."
              : mode === "live"
                ? "Lancia la sfida live"
                : opponents.length > 1
                  ? "Lancia la sfida di gruppo"
                  : "Lancia la sfida"}
          </button>
          <Link to="/sfide" className="btn btn-outline-secondary">
            Annulla
          </Link>
        </div>

        <p className={`${styles.meta} mt-3`}>
          Gli inviti via email partono subito. Differita: gli invitati hanno 72 ore per giocare, e se qualcuno non gioca
          la sfida si chiude alla scadenza con chi ha giocato. Live: parte quando tutti hanno risposto o quando la
          avvii tu. Le risposte corrette si vedono solo a sfida conclusa.
        </p>
      </div>
    </div>
  )
}

export default NuovaSfida
