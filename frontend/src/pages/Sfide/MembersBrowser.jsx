import { useEffect, useRef, useState } from "react"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import UserAvatar from "../../components/UserAvatar/UserAvatar"
import UserTitle from "../../components/UserAvatar/UserTitle"
import styles from "./Sfide.module.css"

const SORTS = [
  { key: "points", label: "Più punti del mese" },
  { key: "level", label: "Livello più alto" },
  { key: "name", label: "Nome A-Z" },
]

/**
 * Elenco dei membri sfidabili con livello e punti del mese: per scegliere
 * l'avversario senza dover conoscere il suo nickname a memoria.
 */
export default function MembersBrowser({ opponents, full, onAdd }) {
  const [open, setOpen] = useState(false)
  const [sort, setSort] = useState("points")
  const [filter, setFilter] = useState("")
  const [page, setPage] = useState(1)
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState("")
  const seq = useRef(0)

  useEffect(() => {
    if (!open) return undefined
    const mySeq = ++seq.current

    // Il filtro per nome aspetta che si finisca di scrivere.
    const timer = setTimeout(
      async () => {
        setLoading(true)
        setError("")
        try {
          const res = await api.get("/challenges/members", { params: { sort, page, q: filter.trim() || undefined } })
          if (mySeq === seq.current) setData(res.data)
        } catch (err) {
          logError(err)
          if (mySeq === seq.current) setError("Non riesco a caricare i membri. Riprova.")
        } finally {
          if (mySeq === seq.current) setLoading(false)
        }
      },
      filter ? 300 : 0
    )

    return () => clearTimeout(timer)
  }, [open, sort, page, filter])

  const toggle = () => {
    setOpen((v) => !v)
  }

  return (
    <div className={styles.membersWrap}>
      <button type="button" className={styles.membersToggle} onClick={toggle} aria-expanded={open} disabled={full}>
        <i className="bi bi-people-fill"></i> {open ? "Nascondi i membri" : "Sfoglia i membri di Midalot"}
        <i className={`bi ${open ? "bi-chevron-up" : "bi-chevron-down"}`}></i>
      </button>

      {open && (
        <div className={styles.membersPanel}>
          <div className="d-flex flex-wrap gap-2 mb-2">
            <input
              className="form-control form-control-sm flex-grow-1"
              style={{ minWidth: 150, width: "auto" }}
              placeholder="Filtra per nome..."
              value={filter}
              maxLength={50}
              onChange={(e) => {
                setFilter(e.target.value)
                setPage(1)
              }}
            />
            <select
              className="form-select form-select-sm"
              style={{ width: "auto" }}
              value={sort}
              onChange={(e) => {
                setSort(e.target.value)
                setPage(1)
              }}
              aria-label="Ordina i membri"
            >
              {SORTS.map((s) => (
                <option key={s.key} value={s.key}>
                  {s.label}
                </option>
              ))}
            </select>
          </div>

          {error && <div className="text-danger small mb-2">{error}</div>}

          {!data && loading && <div className={styles.meta}>Caricamento...</div>}

          {data && (
            <>
              <div className={styles.meta}>
                {data.total} {data.total === 1 ? "membro" : "membri"}
                {sort === "points" && <> · punti di {data.month}</>}
              </div>

              <div className={`${styles.memberList} ${loading ? styles.memberListBusy : ""}`}>
                {data.users.map((u) => {
                  const added = opponents.some((o) => o.id === u.id)
                  return (
                    <button
                      key={u.id}
                      type="button"
                      className={`${styles.memberRow} ${added ? styles.memberAdded : ""}`}
                      disabled={added || full}
                      onClick={() => onAdd(u)}
                    >
                      <UserAvatar url={u.avatar_url} frame={u.avatar_frame} name={u.nickname} size={38} />
                      <span className={styles.memberName}>
                        <strong>{u.nickname}</strong> <UserTitle title={u.avatar_title} />
                      </span>
                      <span className={styles.memberStats}>
                        <span className={styles.memberLevel}>Lv {u.level}</span>
                        <span className={styles.meta}>{u.points.toLocaleString("it-IT")} pt</span>
                      </span>
                      <span className={styles.memberAction}>
                        {added ? <i className="bi bi-check-circle-fill"></i> : <i className="bi bi-plus-circle"></i>}
                      </span>
                    </button>
                  )
                })}
                {data.users.length === 0 && <div className={styles.meta}>Nessun membro trovato.</div>}
              </div>

              {data.last_page > 1 && (
                <div className="d-flex align-items-center justify-content-between mt-2">
                  <button type="button" className={styles.membersPage} disabled={page <= 1 || loading} onClick={() => setPage((p) => p - 1)}>
                    <i className="bi bi-chevron-left"></i> Indietro
                  </button>
                  <span className={styles.meta}>
                    Pagina {data.page} di {data.last_page}
                  </span>
                  <button
                    type="button"
                    className={styles.membersPage}
                    disabled={page >= data.last_page || loading}
                    onClick={() => setPage((p) => p + 1)}
                  >
                    Avanti <i className="bi bi-chevron-right"></i>
                  </button>
                </div>
              )}
            </>
          )}
        </div>
      )}
    </div>
  )
}
