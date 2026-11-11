import { useCallback, useEffect, useMemo, useState } from "react"
import { useSearchParams } from "react-router-dom"
import api from "../../service/api"
import { useAuth } from "../../context/useAuth"
import { logError } from "../../utils/logger"
import AvatarStage from "../../components/Avatar3D/AvatarStage"
import PortraitCapture from "../../components/Avatar3D/PortraitCapture"
import Achievements from "./Achievements"
import UserAvatar from "../../components/UserAvatar/UserAvatar"
import UserTitle from "../../components/UserAvatar/UserTitle"
import {
  BEARDS,
  BROWS,
  DEFAULT_HAIR,
  EYE_COLORS,
  HAIR_COLORS,
  HAIR_STYLES,
  MOUTHS,
  SKIN_TONES,
} from "../../components/Avatar3D/options"
import { FACE_SHAPES } from "../../components/Avatar3D/head"
import { ITEM_MODELS } from "../../components/Avatar3D/items"
import { PALETTES, swatchColor, variantFor } from "../../components/Avatar3D/palettes"
import { RARITY, SLOTS } from "../../components/Avatar3D/catalog"
import styles from "./Avatar.module.css"

const EMBLEM_SYMBOLS = [
  { key: "star", label: "Stella" },
  { key: "cross", label: "Croce" },
  { key: "circle", label: "Anello" },
  { key: "book", label: "Libro" },
]
const EMBLEM_COLORS = ["#2f5fd0", "#c42828", "#2f9b5a", "#6c4bd8", "#1d1b2e", "#ffd54a", "#ffffff", "#f08a24"]
const REQUIRED_SLOTS = ["torso", "legs", "feet"]

function Chips({ options, value, onChange }) {
  return (
    <div className="d-flex flex-wrap gap-2">
      {options.map((o) => (
        <button key={o.key} type="button" className={`${styles.chip} ${value === o.key ? styles.chipActive : ""}`} onClick={() => onChange(o.key)}>
          {o.label}
        </button>
      ))}
    </div>
  )
}

function Swatches({ colors, value, onChange }) {
  return (
    <div className={styles.swatches}>
      {colors.map((c) => (
        <button
          key={c}
          type="button"
          className={`${styles.swatch} ${value === c ? styles.swatchActive : ""}`}
          style={{ background: c }}
          onClick={() => onChange(c)}
          aria-label={c}
        />
      ))}
    </div>
  )
}

function Coins({ value }) {
  return (
    <span className={styles.coins}>
      <i className="bi bi-coin"></i> {value}
    </span>
  )
}

// "2 g 5 h", "5 h 10 min": quanto manca a una data.
function timeLeft(iso) {
  const ms = new Date(iso).getTime() - Date.now()
  if (!(ms > 0)) return "meno di un minuto"
  const min = Math.floor(ms / 60000)
  const d = Math.floor(min / 1440)
  const h = Math.floor((min % 1440) / 60)
  if (d > 0) return `${d} g ${h} h`
  if (h > 0) return `${h} h ${min % 60} min`
  return `${Math.max(1, min)} min`
}

function Price({ it }) {
  return (
    <>
      {it.original_price != null && <s className={styles.oldPrice}>{it.original_price}</s>} <Coins value={it.price} />
    </>
  )
}

function apiError(err, fallback) {
  return err?.response?.data?.message || fallback
}

export default function Avatar() {
  const { user, refreshUser, updateUser } = useAuth()
  const [searchParams, setSearchParams] = useSearchParams()
  const vista = searchParams.get("vista")
  const mode = vista === "negozio" ? "shop" : vista === "obiettivi" ? "goals" : "wardrobe"

  const [data, setData] = useState(null)
  const [draft, setDraft] = useState(null)
  const [tab, setTab] = useState(vista === "negozio" ? "all" : "look")
  const [tryOn, setTryOn] = useState(null) // oggetto del negozio in prova
  const [confirmBuy, setConfirmBuy] = useState(null)
  // Filtri del negozio
  const [shopQuery, setShopQuery] = useState("")
  const [shopFlags, setShopFlags] = useState([])
  const [shopRarity, setShopRarity] = useState("")
  const [shopSort, setShopSort] = useState("default")
  const [themeSel, setThemeSel] = useState("")
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState(null)
  const [loadError, setLoadError] = useState(null)
  const [compact] = useState(() => window.matchMedia?.("(max-width: 991px)").matches ?? false)
  const [portraitConfig, setPortraitConfig] = useState(null)

  // Il ritratto si carica in sottofondo: se non riesce, l'avatar resta comunque salvato.
  const uploadPortrait = useCallback(
    async (blob) => {
      setPortraitConfig(null)
      if (!blob) return
      try {
        const form = new FormData()
        form.append("image", blob, "avatar.png")
        const res = await api.post("/avatar/portrait", form)
        updateUser?.({ avatar_url: res.data.avatar_url })
      } catch (err) {
        logError(err)
      }
    },
    [updateUser]
  )

  useEffect(() => {
    let alive = true

    api
      .get("/avatar")
      .then((res) => {
        if (!alive) return
        setData(res.data)
        setDraft(res.data.config)
        // Gli oggetti nuovi si vedono qui con l'etichetta "Nuovo": l'avviso non serve piu'.
        if (res.data.unseen?.length) api.post("/avatar/news/seen").catch(() => {})
      })
      .catch((err) => {
        logError(err)
        if (alive) setLoadError("Non riesco a caricare il tuo avatar. Riprova tra poco.")
      })

    return () => {
      alive = false
    }
  }, [])

  const owned = useMemo(() => new Set(data?.owned || []), [data])
  const unseen = useMemo(() => new Set(data?.unseen || []), [data])
  // Solo gli oggetti che il frontend sa disegnare.
  const catalog = useMemo(() => (data?.catalog || []).filter((it) => ITEM_MODELS[it.key]), [data])

  if (loadError) return <div className="container py-5 text-center text-danger">{loadError}</div>
  if (!data || !draft) return <div className="container py-5 text-center">Caricamento avatar...</div>

  const level = data.level
  const dirty = JSON.stringify(draft) !== JSON.stringify(data.config)

  const preview = tryOn ? { ...draft, equipped: { ...draft.equipped, [tryOn.slot]: tryOn.key } } : draft

  const update = (patch) => setDraft((prev) => ({ ...prev, ...patch }))

  const equip = (it) =>
    setDraft((prev) => {
      const equipped = { ...prev.equipped }
      if (equipped[it.slot] === it.key) {
        if (REQUIRED_SLOTS.includes(it.slot)) return prev
        delete equipped[it.slot]
      } else {
        equipped[it.slot] = it.key
      }
      return { ...prev, equipped }
    })

  const switchMode = (next) => {
    setTryOn(null)
    setConfirmBuy(null)
    setMessage(null)
    setTab(next === "shop" ? "all" : "look")
    setSearchParams(next === "shop" ? { vista: "negozio" } : next === "goals" ? { vista: "obiettivi" } : {}, { replace: true })
  }

  async function save() {
    setBusy(true)
    setMessage(null)
    try {
      const res = await api.put("/avatar", draft)
      setData(res.data)
      setDraft(res.data.config)
      setMessage({ ok: true, text: "Avatar salvato!" })
      const eq = res.data.config.equipped || {}
      updateUser?.({
        avatar_frame: eq.frame || null,
        avatar_title_label: res.data.catalog.find((it) => it.key === eq.title)?.name || null,
      })
      // Foto del nuovo avatar per sfide e classifiche (vedi uploadPortrait).
      setPortraitConfig(res.data.config)
    } catch (err) {
      logError(err)
      setMessage({ ok: false, text: apiError(err, "Salvataggio non riuscito.") })
    } finally {
      setBusy(false)
    }
  }

  async function buy(it) {
    setBusy(true)
    setMessage(null)
    try {
      const res = await api.post(`/avatar/shop/${it.key}`)
      setData(res.data)
      // Lo indossa subito (resta da salvare).
      setDraft((prev) => ({ ...prev, equipped: { ...prev.equipped, [it.slot]: it.key } }))
      setTryOn(null)
      setConfirmBuy(null)
      setMessage({ ok: true, text: `${res.data.message}. Ricordati di salvare l'avatar!` })
      refreshUser?.()
    } catch (err) {
      logError(err)
      setMessage({ ok: false, text: apiError(err, "Acquisto non riuscito.") })
      setConfirmBuy(null)
    } finally {
      setBusy(false)
    }
  }

  // ------------------------------------------------------------ guardaroba

  const wardrobeItems = catalog.filter((it) => it.slot === tab)
  const nextUnlock = catalog.find((it) => it.unlock_level && it.unlock_level > level)

  // Colori dell'oggetto indossato nella scheda aperta (se ha una tavolozza).
  function colorPicker() {
    const itemKey = draft.equipped[tab]
    const palette = PALETTES[itemKey]
    const status = data.palettes?.[itemKey]
    if (!palette || !status) return null

    const current = variantFor(itemKey, draft.itemColors).key
    const name = catalog.find((it) => it.key === itemKey)?.name

    return (
      <div className={styles.colorBox}>
        <div className={styles.label}>Colore: {name}</div>
        <div className={styles.swatches}>
          {palette.map((c) => {
            const info = status.find((x) => x.key === c.key)
            const locked = !info?.unlocked
            return (
              <button
                key={c.key}
                type="button"
                disabled={locked}
                title={locked ? `${c.label}: si sblocca al livello ${info?.level}` : c.label}
                aria-label={c.label}
                className={`${styles.swatch} ${current === c.key ? styles.swatchActive : ""} ${locked ? styles.swatchLocked : ""}`}
                style={{ background: swatchColor(c) }}
                onClick={() => setDraft((prev) => ({ ...prev, itemColors: { ...(prev.itemColors || {}), [itemKey]: c.key } }))}
              >
                {locked && <i className="bi bi-lock-fill"></i>}
              </button>
            )
          })}
        </div>
        {status.some((x) => !x.unlocked) && (
          <div className={styles.subLabel}>
            Altri colori si sbloccano salendo di livello (prossimo: Lv {Math.min(...status.filter((x) => !x.unlocked).map((x) => x.level))}).
          </div>
        )}
      </div>
    )
  }

  function wardrobeCard(it) {
    const mine = owned.has(it.key)
    const active = draft.equipped[it.slot] === it.key
    const rarity = RARITY[it.rarity] || RARITY.comune

    let status
    if (mine) status = active ? <><i className="bi bi-check-circle-fill"></i> Indossato</> : "Tocca per indossare"
    else if (it.unlock_level) status = <><i className="bi bi-lock-fill"></i> Si sblocca al Lv {it.unlock_level}</>
    else if (it.price != null) status = <><i className="bi bi-shop"></i> Nel negozio · <Price it={it} /></>
    else status = <><i className="bi bi-award"></i> {data.achievement_items?.[it.key] || "Premio di un obiettivo"}</>

    return (
      <button
        key={it.key}
        type="button"
        className={`${styles.item} ${active ? styles.itemActive : ""} ${!mine ? styles.itemLocked : ""}`}
        style={{ "--rarity": rarity.color }}
        onClick={() => {
          if (mine) equip(it)
          else if (it.price != null) {
            switchMode("shop")
            setTryOn(it)
          }
        }}
        disabled={!mine && it.price == null}
      >
        <span className={styles.rarity}>
          {rarity.label}
          {unseen.has(it.key) && <span className={styles.newBadge}>Nuovo</span>}
        </span>
        {it.slot === "frame" && <UserAvatar url={user?.avatar_url} name={user?.nickname} frame={it.key} size={44} className="my-1" />}
        <span className={styles.itemName}>{it.slot === "title" ? <UserTitle title={it.name} /> : it.name}</span>
        <span className={styles.itemLevel}>{status}</span>
      </button>
    )
  }

  // ------------------------------------------------------------ negozio

  const catalogByKey = Object.fromEntries(catalog.map((it) => [it.key, it]))
  const query = shopQuery.trim().toLowerCase()
  const filtering = query !== "" || shopFlags.length > 0 || shopRarity !== "" || shopSort !== "default"
  const RARITY_ORDER = { base: 0, comune: 1, raro: 2, epico: 3, leggendario: 4 }

  // Ricerca, filtri e ordinamento (valgono anche dentro i temi).
  function applyShopFilters(list) {
    const out = list.filter((it) => {
      if (query && !it.name.toLowerCase().includes(query)) return false
      if (shopRarity && it.rarity !== shopRarity) return false
      if (shopFlags.includes("notMine") && owned.has(it.key)) return false
      if (shopFlags.includes("affordable") && (it.price > data.coins || level < it.shop_min_level)) return false
      if (shopFlags.includes("sale") && !(it.discount > 0)) return false
      if (shopFlags.includes("exclusive") && !it.exclusive) return false
      return true
    })
    if (shopSort === "priceAsc") out.sort((a, b) => a.price - b.price)
    if (shopSort === "priceDesc") out.sort((a, b) => b.price - a.price)
    if (shopSort === "rarity") out.sort((a, b) => (RARITY_ORDER[b.rarity] ?? 0) - (RARITY_ORDER[a.rarity] ?? 0))
    return out
  }

  function toggleFlag(flag) {
    setShopFlags((prev) => (prev.includes(flag) ? prev.filter((f) => f !== flag) : [...prev, flag]))
  }

  function resetShopFilters() {
    setShopQuery("")
    setShopFlags([])
    setShopRarity("")
    setShopSort("default")
  }

  const shopItems = applyShopFilters(catalog.filter((it) => it.price != null && (tab === "all" || it.slot === tab)))
  // In "Tutto" (senza filtri) la vetrina sta in un riquadro a parte; altrimenti gli oggetti in vetrina restano nella lista con lo sconto.
  const featured = tab === "all" && !filtering ? shopItems.filter((it) => it.showcase) : []
  const regular = shopItems
    .filter((it) => !featured.includes(it))
    .sort((a, b) => Number(b.exclusive) - Number(a.exclusive))
  const shopSlots = SLOTS.filter((s) => catalog.some((it) => it.price != null && it.slot === s.key))

  function shopCard(it) {
    const mine = owned.has(it.key)
    const tooLow = level < it.shop_min_level
    const poor = data.coins < it.price
    const trying = tryOn?.key === it.key
    const rarity = RARITY[it.rarity] || RARITY.comune

    return (
      <div key={it.key} className={`${styles.item} ${trying ? styles.itemActive : ""}`} style={{ "--rarity": rarity.color }}>
        <span className={styles.rarity}>
          {rarity.label}
          {it.discount > 0 && !it.exclusive && <span className={styles.saleTag}>-{it.discount}%</span>}
          {it.exclusive && <span className={styles.exclusiveTag}>Esclusivo</span>}
        </span>
        {it.slot === "frame" && <UserAvatar url={user?.avatar_url} name={user?.nickname} frame={it.key} size={44} className="my-1" />}
        <span className={styles.itemName}>{it.slot === "title" ? <UserTitle title={it.name} /> : it.name}</span>
        <span className={styles.itemLevel}>
          <Price it={it} />
          {tooLow && <> · Lv {it.shop_min_level}</>}
          {it.exclusive && it.until && <div>Solo per {timeLeft(it.until)}</div>}
        </span>

        {mine ? (
          <span className={styles.ownedTag}>
            <i className="bi bi-check-circle-fill"></i> Già tuo
          </span>
        ) : (
          <div className="d-flex gap-1 flex-wrap mt-1">
            <button type="button" className={styles.smallBtn} onClick={() => setTryOn(trying ? null : it)}>
              {trying ? "Togli" : "Prova"}
            </button>
            {confirmBuy === it.key ? (
              <button type="button" className={`${styles.smallBtn} ${styles.buyBtn}`} disabled={busy} onClick={() => buy(it)}>
                Conferma
              </button>
            ) : (
              <button
                type="button"
                className={`${styles.smallBtn} ${styles.buyBtn}`}
                disabled={busy || tooLow || poor}
                title={tooLow ? `Serve il livello ${it.shop_min_level}` : poor ? "Monete insufficienti" : ""}
                onClick={() => setConfirmBuy(it.key)}
              >
                Compra
              </button>
            )}
          </div>
        )}
      </div>
    )
  }

  const focus =
    mode === "wardrobe" && (tab === "look" || tab === "face")
      ? "face"
      : tab === "head" || tab === "face" || tab === "hair"
        ? "head"
        : "body"

  return (
    <div className="container py-4">
      {portraitConfig && <PortraitCapture config={portraitConfig} onCaptured={uploadPortrait} />}
      <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div className={styles.modeSwitch}>
          <button type="button" className={mode === "wardrobe" ? styles.modeActive : ""} onClick={() => switchMode("wardrobe")}>
            <i className="bi bi-person-badge"></i> Il mio avatar
          </button>
          <button type="button" className={mode === "shop" ? styles.modeActive : ""} onClick={() => switchMode("shop")}>
            <i className="bi bi-shop"></i> Negozio
          </button>
          <button type="button" className={mode === "goals" ? styles.modeActive : ""} onClick={() => switchMode("goals")}>
            <i className="bi bi-award"></i> Obiettivi
          </button>
        </div>

        <div className="d-flex flex-wrap align-items-center gap-2">
          <span className={styles.levelTag}>Lv {level}</span>
          <span className={styles.wallet}>
            <Coins value={data.coins} /> monete
          </span>
        </div>
      </div>

      {nextUnlock && mode === "wardrobe" && (
        <div className={styles.next}>
          <i className="bi bi-gift"></i> Prossimo regalo: <strong>{nextUnlock.name}</strong> al livello {nextUnlock.unlock_level}
        </div>
      )}

      <div className="row g-3">
        <div className="col-lg-5">
          <div className={styles.stageWrap}>
            <div className={styles.stageCard}>
              <AvatarStage config={preview} height={compact ? 340 : 460} focus={focus} />
            </div>
            <div className={styles.lookCard}>
              <UserAvatar url={user?.avatar_url} name={user?.nickname} frame={preview.equipped?.frame} size={52} />
              <div>
                <div className={styles.lookLabel}>Come ti vedono gli altri</div>
                <strong>{user?.nickname}</strong> <UserTitle title={catalog.find((it) => it.key === preview.equipped?.title)?.name} />
                {!user?.avatar_url && <div className={styles.lookHint}>Salva l&apos;avatar per creare il tuo ritratto.</div>}
              </div>
            </div>
            {tryOn && (
              <div className={styles.tryOnBar}>
                In prova: <strong>{tryOn.name}</strong>
                <button type="button" className={styles.smallBtn} onClick={() => setTryOn(null)}>
                  Togli
                </button>
              </div>
            )}
            <div className={`${styles.saveBar} ${dirty ? styles.saveBarDirty : ""}`}>
              {message && <span className={message.ok ? styles.msgOk : styles.msgErr}>{message.text}</span>}
              <button type="button" className={styles.saveBtn} disabled={!dirty || busy} onClick={save}>
                {busy ? "Salvataggio..." : dirty ? "Salva avatar" : "Salvato"}
              </button>
            </div>
          </div>
        </div>

        <div className="col-lg-7">
          {mode === "goals" && <Achievements />}
          <div className={styles.tabs} hidden={mode === "goals"}>
            {mode === "wardrobe" ? (
              <>
                <button type="button" className={`${styles.tab} ${tab === "look" ? styles.tabActive : ""}`} onClick={() => setTab("look")}>
                  <i className="bi bi-person-circle"></i> Aspetto
                </button>
                <button type="button" className={`${styles.tab} ${tab === "hair" ? styles.tabActive : ""}`} onClick={() => setTab("hair")}>
                  <i className="bi bi-scissors"></i> Capelli
                </button>
                {SLOTS.map((s) => (
                  <button key={s.key} type="button" className={`${styles.tab} ${tab === s.key ? styles.tabActive : ""}`} onClick={() => setTab(s.key)}>
                    <i className={`bi ${s.icon}`}></i> {s.label}
                  </button>
                ))}
              </>
            ) : (
              <>
                <button type="button" className={`${styles.tab} ${tab === "all" ? styles.tabActive : ""}`} onClick={() => setTab("all")}>
                  Tutto
                </button>
                {data.themes?.length > 0 && (
                  <button type="button" className={`${styles.tab} ${tab === "temi" ? styles.tabActive : ""}`} onClick={() => setTab("temi")}>
                    <i className="bi bi-collection-fill"></i> Temi
                  </button>
                )}
                {shopSlots.map((s) => (
                  <button key={s.key} type="button" className={`${styles.tab} ${tab === s.key ? styles.tabActive : ""}`} onClick={() => setTab(s.key)}>
                    <i className={`bi ${s.icon}`}></i> {s.label}
                  </button>
                ))}
              </>
            )}
          </div>

          <div className={styles.panel} hidden={mode === "goals"}>
            {mode === "shop" ? (
              <>
                <p className={styles.hint}>
                  Le monete si guadagnano giocando: ogni partita ne dà un po&apos;, ogni livello raggiunto regala un bonus e anche solo entrare ogni giorno ne vale qualcuna (con un premio extra se entri almeno 3 giorni a settimana). Prova gli
                  oggetti sull&apos;avatar prima di comprarli.
                </p>
                {data.promo && (
                  <div className={styles.promoBanner}>
                    <i className="bi bi-gift-fill"></i>
                    <span>
                      <strong>{data.promo.name}</strong>: -{data.promo.discount}%{" "}
                      {data.promo.scope === "all" ? "su tutto il negozio" : "sugli oggetti in vetrina"} · finisce tra {timeLeft(data.promo.ends_at)}
                    </span>
                  </div>
                )}
                <div className={styles.filterBar}>
                  <div className={styles.searchBox}>
                    <i className="bi bi-search"></i>
                    <input
                      type="search"
                      placeholder="Cerca un oggetto..."
                      value={shopQuery}
                      maxLength={40}
                      onChange={(e) => setShopQuery(e.target.value)}
                      aria-label="Cerca un oggetto"
                    />
                  </div>
                  <select className={styles.sortSelect} value={shopSort} onChange={(e) => setShopSort(e.target.value)} aria-label="Ordina">
                    <option value="default">Consigliati</option>
                    <option value="priceAsc">Prezzo: dal più basso</option>
                    <option value="priceDesc">Prezzo: dal più alto</option>
                    <option value="rarity">Più rari prima</option>
                  </select>
                </div>
                <div className={styles.pills}>
                  {[
                    ["notMine", "bi-bag", "Da comprare"],
                    ["affordable", "bi-coin", "Posso permettermelo"],
                    ["sale", "bi-percent", "In saldo"],
                    ["exclusive", "bi-gem", "Esclusivi"],
                  ].map(([key, icon, label]) => (
                    <button
                      key={key}
                      type="button"
                      className={`${styles.pill} ${shopFlags.includes(key) ? styles.pillActive : ""}`}
                      onClick={() => toggleFlag(key)}
                    >
                      <i className={`bi ${icon}`}></i> {label}
                    </button>
                  ))}
                  {["comune", "raro", "epico", "leggendario"].map((r) => (
                    <button
                      key={r}
                      type="button"
                      className={`${styles.pill} ${shopRarity === r ? styles.pillActive : ""}`}
                      style={{ "--rarity": (RARITY[r] || RARITY.comune).color }}
                      onClick={() => setShopRarity(shopRarity === r ? "" : r)}
                    >
                      <span className={styles.rarityDot}></span> {(RARITY[r] || RARITY.comune).label}
                    </button>
                  ))}
                  {filtering && (
                    <button type="button" className={`${styles.pill} ${styles.pillReset}`} onClick={resetShopFilters}>
                      <i className="bi bi-x-circle"></i> Azzera
                    </button>
                  )}
                </div>
                {tab !== "temi" && (
                  <div className={styles.resultCount}>
                    {shopItems.length} {shopItems.length === 1 ? "oggetto" : "oggetti"}
                  </div>
                )}
                {featured.length > 0 && (
                  <div className={styles.showcase}>
                    <div className={styles.showcaseHead}>
                      <strong>
                        <i className="bi bi-stars"></i> In vetrina questa settimana
                      </strong>
                      <span>Sconto fino al {data.showcase?.discount ?? 30}% · cambia tra {timeLeft(data.showcase?.ends_at)}</span>
                    </div>
                    <div className={styles.grid}>{featured.map(shopCard)}</div>
                    <p className={styles.showcaseNote}>
                      La vetrina cambia ogni lunedì con nuovi oggetti scontati. Finita la settimana tornano al prezzo normale, ma restano
                      nel negozio. A volte compaiono oggetti esclusivi: si comprano solo per un periodo limitato!
                    </p>
                  </div>
                )}
                {tab === "temi" ? (
                  <div className="d-flex flex-column gap-3">
                    <p className={styles.hint}>
                      Ogni tema è un personaggio completo. Collezionali tutti: chi completa un tema riceve in regalo un titolo
                      speciale che non si può comprare!
                    </p>
                    <div className={styles.pills}>
                      <button type="button" className={`${styles.pill} ${themeSel === "" ? styles.pillActive : ""}`} onClick={() => setThemeSel("")}>
                        Tutti i temi
                      </button>
                      {data.themes.map((th) => (
                        <button
                          key={th.key}
                          type="button"
                          className={`${styles.pill} ${themeSel === th.key ? styles.pillActive : ""}`}
                          onClick={() => setThemeSel(th.key)}
                        >
                          <i className={`bi ${th.icon}`}></i> {th.name}
                        </button>
                      ))}
                    </div>
                    {data.themes.filter((th) => !themeSel || th.key === themeSel).map((th) => {
                      const items = applyShopFilters(th.items.map((k) => catalogByKey[k]).filter(Boolean))
                      if (filtering && items.length === 0) return null
                      return (
                        <div key={th.key} className={styles.themeBox}>
                          <div className={styles.themeHead}>
                            <strong>
                              <i className={`bi ${th.icon}`}></i> {th.name}
                            </strong>
                            <span className={styles.themeProgress}>
                              {th.owned}/{th.items.length}
                            </span>
                          </div>
                          <div className={styles.themeBar}>
                            <span style={{ width: `${(th.owned / th.items.length) * 100}%` }}></span>
                          </div>
                          {th.reward && (
                            <div className={styles.themeReward}>
                              {th.complete ? (
                                <>
                                  <i className="bi bi-trophy-fill"></i> Tema completato! Hai ricevuto il titolo <UserTitle title={th.reward.name} />
                                </>
                              ) : (
                                <>
                                  <i className="bi bi-gift"></i> Completa il tema per ricevere il titolo <UserTitle title={th.reward.name} />
                                </>
                              )}
                            </div>
                          )}
                          <div className={styles.grid}>{items.map(shopCard)}</div>
                        </div>
                      )
                    })}
                  </div>
                ) : regular.length > 0 ? (
                  <div className={styles.grid}>{regular.map(shopCard)}</div>
                ) : (
                  featured.length === 0 && (
                    <div className={styles.emptyShop}>
                      Nessun oggetto con questi filtri.{" "}
                      <button type="button" className="btn btn-link btn-sm p-0" onClick={resetShopFilters}>
                        Azzera i filtri
                      </button>
                    </div>
                  )
                )}
              </>
            ) : tab === "hair" ? (
              <div className="d-flex flex-column gap-3">
                <div>
                  <div className={styles.label}>Acconciatura</div>
                  <div className={styles.hairGrid}>
                    {HAIR_STYLES.map((h) => (
                      <button
                        key={h.key}
                        type="button"
                        className={`${styles.hairCard} ${draft.hair === h.key ? styles.hairCardActive : ""}`}
                        onClick={() => update({ hair: h.key })}
                      >
                        <i className={`bi ${h.icon}`}></i>
                        {h.label}
                      </button>
                    ))}
                  </div>
                </div>
                <div>
                  <div className={styles.label}>Colore</div>
                  <Swatches colors={HAIR_COLORS} value={draft.hairColor} onChange={(hairColor) => update({ hairColor })} />
                </div>
                {draft.gender === "m" && (
                  <div>
                    <div className={styles.label}>Barba</div>
                    <Chips options={BEARDS} value={draft.beard} onChange={(beard) => update({ beard })} />
                    <div className={styles.subLabel}>La barba ha lo stesso colore dei capelli.</div>
                  </div>
                )}
              </div>
            ) : tab === "look" ? (
              <div className="d-flex flex-column gap-3">
                <div>
                  <div className={styles.label}>Personaggio</div>
                  <Chips
                    options={[
                      { key: "m", label: "Ragazzo" },
                      { key: "f", label: "Ragazza" },
                    ]}
                    value={draft.gender}
                    onChange={(g) =>
                      // Cambiando personaggio si parte dai capelli tipici; poi si scelgono in "Capelli".
                      g !== draft.gender &&
                      update({
                        gender: g,
                        hair: DEFAULT_HAIR[g],
                        beard: "nessuna",
                      })
                    }
                  />
                </div>
                <div>
                  <div className={styles.label}>Pelle</div>
                  <Swatches colors={SKIN_TONES} value={draft.skin} onChange={(skin) => update({ skin })} />
                </div>
                <div>
                  <div className={styles.label}>Forma del viso</div>
                  <Chips options={FACE_SHAPES} value={draft.faceShape} onChange={(faceShape) => update({ faceShape })} />
                </div>
                <div>
                  <div className={styles.label}>Sopracciglia</div>
                  <Chips options={BROWS} value={draft.brows} onChange={(brows) => update({ brows })} />
                </div>
                <div>
                  <div className={styles.label}>Bocca</div>
                  <Chips options={MOUTHS} value={draft.mouth} onChange={(mouth) => update({ mouth })} />
                </div>
                <div>
                  <div className={styles.label}>Lentiggini</div>
                  <Chips
                    options={[
                      { key: "no", label: "No" },
                      { key: "si", label: "Sì" },
                    ]}
                    value={draft.freckles ? "si" : "no"}
                    onChange={(v) => update({ freckles: v === "si" })}
                  />
                </div>
                <div>
                  <div className={styles.label}>Occhi</div>
                  <Swatches colors={EYE_COLORS} value={draft.eyes} onChange={(eyes) => update({ eyes })} />
                </div>
                {owned.has("scudo") && (
                  <div>
                    <div className={styles.label}>Stemma dello scudo</div>
                    <div className="mb-2">
                      <Chips options={EMBLEM_SYMBOLS} value={draft.emblem.symbol} onChange={(symbol) => update({ emblem: { ...draft.emblem, symbol } })} />
                    </div>
                    <div className={styles.subLabel}>Sfondo</div>
                    <Swatches colors={EMBLEM_COLORS} value={draft.emblem.bg} onChange={(bg) => update({ emblem: { ...draft.emblem, bg } })} />
                    <div className={styles.subLabel}>Simbolo</div>
                    <Swatches colors={EMBLEM_COLORS} value={draft.emblem.fg} onChange={(fg) => update({ emblem: { ...draft.emblem, fg } })} />
                  </div>
                )}
              </div>
            ) : (
              <>
              {colorPicker()}
              <div className={styles.grid}>
                {wardrobeItems.map(wardrobeCard)}
                {wardrobeItems.length === 0 && <div className="text-muted">Nessun oggetto in questa categoria.</div>}
              </div>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
