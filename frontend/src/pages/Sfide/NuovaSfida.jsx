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
  const [opponent, setOpponent] = useState(null)
  const [categories, setCategories] = useState([])
  const [allCount, setAllCount] = useState(0)
  const [selected, setSelected] = useState([])
  const [sending, setSending] = useState(false)
  const [error, setError] = useState("")
  const searchSeq = useRef(0)

  useEffect(() => {
    api
      .get("/challenges/categories")
      .then((res) => {
        setCategories(res.data.categories)
        setAllCount(res.data.all_questions_count)
      })
      .catch(logError)
  }, [])

  useEffect(() => {
    const term = query.trim()
    const seq = ++searchSeq.current

    if (term.length < 2 || opponent) return undefined

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
  }, [query, opponent])

  function toggleCategory(id) {
    setSelected((prev) => (prev.includes(id) ? prev.filter((c) => c !== id) : [...prev, id]))
  }

  const availableQuestions = selected.length
    ? categories.filter((c) => selected.includes(c.id)).reduce((sum, c) => sum + c.questions_count, 0)
    : allCount

  async function submit() {
    if (!opponent || sending) return

    setSending(true)
    setError("")

    try {
      const res = await api.post("/challenges", {
        opponent_id: opponent.id,
        category_ids: selected.length ? selected : null,
      })
      notifyChallengesChanged()
      navigate(`/sfide/${res.data.id}`, { replace: true })
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
          Scegli chi sfidare e le categorie: le 7 domande vengono estratte dai Training.
        </p>

        <h2 className={`${styles.sectionTitle} mt-4`}>1. Avversario</h2>

        {opponent ? (
          <div className={styles.userResult}>
            <span>
              {opponent.nickname} <span className={styles.meta}>(Lv {opponent.level})</span>
            </span>
            <button
              type="button"
              className="btn btn-sm btn-outline-secondary"
              onClick={() => {
                setOpponent(null)
                setQuery("")
                setResults([])
              }}
            >
              Cambia
            </button>
          </div>
        ) : (
          <>
            <input
              className="form-control"
              placeholder="Cerca per nickname"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              maxLength={50}
            />
            <div className="mt-2">
              {results.map((u) => (
                <button key={u.id} type="button" className={styles.userResult} onClick={() => setOpponent(u)}>
                  <span>{u.nickname}</span>
                  <span className={styles.meta}>Lv {u.level}</span>
                </button>
              ))}
              {query.trim().length >= 2 && searchedTerm === query.trim() && results.length === 0 && (
                <div className={styles.meta}>Nessun giocatore trovato.</div>
              )}
            </div>
          </>
        )}

        <h2 className={`${styles.sectionTitle} mt-4`}>2. Categorie</h2>
        <p className={styles.meta}>Nessuna selezione = tutte le categorie.</p>

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

        {error && <div className="alert alert-danger mt-3">{error}</div>}

        <div className="d-flex gap-2 mt-4">
          <button className="btn btn-warning fw-bold" disabled={!opponent || sending || availableQuestions < 7} onClick={submit}>
            {sending ? "Invio..." : "Lancia la sfida"}
          </button>
          <Link to="/sfide" className="btn btn-outline-secondary">
            Annulla
          </Link>
        </div>

        <p className={`${styles.meta} mt-3`}>
          L'avversario avrà 72 ore per giocare. Le risposte corrette si vedono solo a sfida conclusa.
        </p>
      </div>
    </div>
  )
}

export default NuovaSfida
