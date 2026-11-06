import { useEffect, useRef, useState } from "react"
import { Link, useNavigate } from "react-router-dom"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { notifyChallengesChanged } from "../../hooks/useChallengeCount"
import styles from "./Sfide.module.css"
import UserAvatar from "../../components/UserAvatar/UserAvatar"

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
  const [selectedSubs, setSelectedSubs] = useState([])
  const [selectedQuizzes, setSelectedQuizzes] = useState([])
  const [expanded, setExpanded] = useState([])
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

  // Sottocategorie: se ce n'e' una sola con lo stesso nome della categoria non
  // aggiunge nulla e si nasconde (i suoi Training restano nell'elenco piatto).
  function subsOf(category) {
    const subs = category.subcategories || []
    if (subs.length === 1 && subs[0].name.trim().toLowerCase() === category.name.trim().toLowerCase()) return []
    return subs
  }

  const quizzesOf = (category) => category.quizzes || []
  const hasDetail = (category) => subsOf(category).length > 0 || quizzesOf(category).length > 1

  // Toglie dalla selezione tutto il dettaglio di una categoria (sottocategorie e Training).
  function dropDetail(category) {
    const subIds = (category.subcategories || []).map((sub) => sub.id)
    const quizIds = quizzesOf(category).map((quiz) => quiz.id)

    setSelectedSubs((prev) => prev.filter((id) => !subIds.includes(id)))
    setSelectedQuizzes((prev) => prev.filter((id) => !quizIds.includes(id)))
  }

  // Categoria intera: se era scelta nel dettaglio passa a "tutta la categoria".
  function toggleCategory(category) {
    dropDetail(category)
    setSelected((prev) => (prev.includes(category.id) ? prev.filter((c) => c !== category.id) : [...prev, category.id]))
  }

  // Sottocategoria intera. Dentro una categoria scelta per intero passa a "solo questa".
  function toggleSub(category, sub) {
    if (selected.includes(category.id)) {
      setSelected((prev) => prev.filter((c) => c !== category.id))
      dropDetail(category)
      setSelectedSubs((prev) => [...prev, sub.id])
      return
    }

    if (selectedSubs.includes(sub.id)) {
      setSelectedSubs((prev) => prev.filter((id) => id !== sub.id))
      return
    }

    const subQuizIds = quizzesOf(category).filter((q) => q.subcategory_id === sub.id).map((q) => q.id)
    setSelectedQuizzes((prev) => prev.filter((id) => !subQuizIds.includes(id)))
    setSelectedSubs((prev) => [...prev, sub.id])
  }

  // Singolo Training. Dentro una scelta piu' ampia passa a "solo questo".
  function toggleQuiz(category, quiz) {
    if (selected.includes(category.id)) {
      setSelected((prev) => prev.filter((c) => c !== category.id))
      dropDetail(category)
      setSelectedQuizzes((prev) => [...prev, quiz.id])
      return
    }

    if (quiz.subcategory_id && selectedSubs.includes(quiz.subcategory_id)) {
      setSelectedSubs((prev) => prev.filter((id) => id !== quiz.subcategory_id))
      setSelectedQuizzes((prev) => [...prev, quiz.id])
      return
    }

    setSelectedQuizzes((prev) => (prev.includes(quiz.id) ? prev.filter((id) => id !== quiz.id) : [...prev, quiz.id]))
  }

  function toggleExpanded(id) {
    setExpanded((prev) => (prev.includes(id) ? prev.filter((c) => c !== id) : [...prev, id]))
  }

  const selectedSubsOf = (category) => (category.subcategories || []).filter((sub) => selectedSubs.includes(sub.id))
  const selectedQuizzesOf = (category) => quizzesOf(category).filter((quiz) => selectedQuizzes.includes(quiz.id))

  const isSubActive = (category, sub) => selected.includes(category.id) || selectedSubs.includes(sub.id)
  const isQuizActive = (category, quiz) =>
    selected.includes(category.id) ||
    selectedQuizzes.includes(quiz.id) ||
    (quiz.subcategory_id != null && selectedSubs.includes(quiz.subcategory_id))

  const detailCount = (category) => selectedSubsOf(category).length + selectedQuizzesOf(category).length

  const hasSelection = selected.length > 0 || selectedSubs.length > 0 || selectedQuizzes.length > 0

  const availableQuestions = hasSelection
    ? categories.reduce(
        (sum, c) =>
          sum +
          (selected.includes(c.id)
            ? c.questions_count
            : selectedSubsOf(c).reduce((n, sub) => n + sub.questions_count, 0) +
              selectedQuizzesOf(c)
                .filter((q) => !(q.subcategory_id != null && selectedSubs.includes(q.subcategory_id)))
                .reduce((n, quiz) => n + quiz.questions_count, 0)),
        0
      )
    : allCount

  // Etichette della selezione: "Disney", "Disney › Marvel" o "Geografia › Capitali d'Europa".
  const selectionLabels = [
    ...categories.filter((c) => selected.includes(c.id)).map((c) => c.name),
    ...categories.flatMap((c) => selectedSubsOf(c).map((sub) => `${c.name} › ${sub.name}`)),
    ...categories.flatMap((c) => selectedQuizzesOf(c).map((quiz) => `${c.name} › ${quiz.title}`)),
  ]

  const visibleResults = results.filter((u) => !opponents.some((o) => o.id === u.id))

  async function submit() {
    if (opponents.length === 0 || sending) return

    setSending(true)
    setError("")

    try {
      const res = await api.post("/challenges", {
        opponent_ids: opponents.map((o) => o.id),
        category_ids: selected.length ? selected : null,
        subcategory_ids: selectedSubs.length ? selectedSubs : null,
        quiz_ids: selectedQuizzes.length ? selectedQuizzes : null,
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
                <UserAvatar url={o.avatar_url} name={o.nickname} size={22} /> {o.nickname} <span className={styles.meta}>Lv {o.level}</span>
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
                  <span className="d-flex align-items-center gap-2">
                    <UserAvatar url={u.avatar_url} name={u.nickname} size={28} />
                    {u.nickname}
                  </span>
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
        <div className={`${styles.hint} ${hasSelection ? "" : styles.hintAll}`}>
          <i className={`bi ${hasSelection ? "bi-funnel-fill" : "bi-check2-circle"}`}></i>
          <span>
            {hasSelection ? (
              <>
                <strong>{selectionLabels.length === 1 ? "1 scelta:" : `${selectionLabels.length} scelte:`}</strong>{" "}
                {selectionLabels.join(", ")}. Le domande saranno pescate solo da qui. Tocca di nuovo per deselezionare.
              </>
            ) : (
              <>
                <strong>Tutte le categorie.</strong> Non hai selezionato nulla, quindi le domande saranno pescate da
                tutti i Training. Tocca una categoria per restringere la scelta, e la freccia accanto per scegliere un
                Training preciso (es. Disney › Peter Pan).
              </>
            )}
          </span>
        </div>

        <div className={styles.categoryGrid}>
          {categories.map((c) => {
            const count = detailCount(c)
            const active = selected.includes(c.id) || count > 0

            return (
              <div key={c.id} className={`${styles.categoryChipWrap} ${active ? styles.categoryChipActive : ""}`}>
                <button type="button" className={styles.categoryChipMain} onClick={() => toggleCategory(c)}>
                  {c.name}
                  <span className={styles.meta}>
                    {count > 0 ? ` · ${count} ${count === 1 ? "scelta" : "scelte"}` : ` · ${c.questions_count} domande`}
                  </span>
                </button>

                {hasDetail(c) && (
                  <button
                    type="button"
                    className={styles.categoryChipCaret}
                    aria-label={`Scegli nel dettaglio dentro ${c.name}`}
                    aria-expanded={expanded.includes(c.id)}
                    onClick={() => toggleExpanded(c.id)}
                  >
                    <i className={`bi ${expanded.includes(c.id) ? "bi-chevron-up" : "bi-chevron-down"}`}></i>
                  </button>
                )}
              </div>
            )
          })}
        </div>

        {categories
          .filter((c) => expanded.includes(c.id) && hasDetail(c))
          .map((c) => {
            const subs = subsOf(c)
            const grouped = new Set(subs.map((sub) => sub.id))
            const loose = quizzesOf(c).filter((quiz) => !grouped.has(quiz.subcategory_id))

            const quizChip = (quiz) => (
              <button
                key={quiz.id}
                type="button"
                className={`${styles.categoryChip} ${isQuizActive(c, quiz) ? styles.categoryChipActive : ""}`}
                onClick={() => toggleQuiz(c, quiz)}
              >
                {quiz.title}
                <span className={styles.meta}> · {quiz.questions_count} domande</span>
              </button>
            )

            return (
              <div key={c.id} className={styles.subPanel}>
                <div className={styles.subPanelTitle}>
                  {c.name}: scegli nel dettaglio <span className={styles.meta}>(facoltativo)</span>
                </div>

                <div className={styles.categoryGrid}>
                  <button
                    type="button"
                    className={`${styles.categoryChip} ${selected.includes(c.id) ? styles.categoryChipActive : ""}`}
                    onClick={() => toggleCategory(c)}
                  >
                    Tutto {c.name}
                    <span className={styles.meta}> · {c.questions_count} domande</span>
                  </button>
                </div>

                {subs.map((sub) => (
                  <div key={sub.id} className={styles.subGroup}>
                    <div className={styles.categoryGrid}>
                      <button
                        type="button"
                        className={`${styles.categoryChip} ${styles.groupChip} ${
                          isSubActive(c, sub) ? styles.categoryChipActive : ""
                        }`}
                        onClick={() => toggleSub(c, sub)}
                      >
                        <i className="bi bi-collection"></i> Tutto {sub.name}
                        <span className={styles.meta}> · {sub.questions_count} domande</span>
                      </button>
                      {quizzesOf(c)
                        .filter((quiz) => quiz.subcategory_id === sub.id)
                        .map(quizChip)}
                    </div>
                  </div>
                ))}

                {loose.length > 0 && (
                  <div className={styles.subGroup}>
                    <div className={styles.categoryGrid}>{loose.map(quizChip)}</div>
                  </div>
                )}
              </div>
            )
          })}

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
