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
  const [tab, setTab] = useState("look")
  const [tryOn, setTryOn] = useState(null) // oggetto del negozio in prova
  const [confirmBuy, setConfirmBuy] = useState(null)
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

  function wardrobeCard(it) {
    const mine = owned.has(it.key)
    const active = draft.equipped[it.slot] === it.key
    const rarity = RARITY[it.rarity] || RARITY.comune

    let status
    if (mine) status = active ? <><i className="bi bi-check-circle-fill"></i> Indossato</> : "Tocca per indossare"
    else if (it.unlock_level) status = <><i className="bi bi-lock-fill"></i> Si sblocca al Lv {it.unlock_level}</>
    else if (it.price != null) status = <><i className="bi bi-shop"></i> Nel negozio · <Coins value={it.price} /></>
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

  const shopItems = catalog.filter((it) => it.price != null && (tab === "all" || it.slot === tab))
  const shopSlots = SLOTS.filter((s) => catalog.some((it) => it.price != null && it.slot === s.key))

  function shopCard(it) {
    const mine = owned.has(it.key)
    const tooLow = level < it.shop_min_level
    const poor = data.coins < it.price
    const trying = tryOn?.key === it.key
    const rarity = RARITY[it.rarity] || RARITY.comune

    return (
      <div key={it.key} className={`${styles.item} ${trying ? styles.itemActive : ""}`} style={{ "--rarity": rarity.color }}>
        <span className={styles.rarity}>{rarity.label}</span>
        {it.slot === "frame" && <UserAvatar url={user?.avatar_url} name={user?.nickname} frame={it.key} size={44} className="my-1" />}
        <span className={styles.itemName}>{it.slot === "title" ? <UserTitle title={it.name} /> : it.name}</span>
        <span className={styles.itemLevel}>
          <Coins value={it.price} />
          {tooLow && <> · Lv {it.shop_min_level}</>}
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
                  Le monete si guadagnano giocando: ogni partita ne dà un po&apos; e ogni livello raggiunto regala un bonus. Prova gli
                  oggetti sull&apos;avatar prima di comprarli.
                </p>
                <div className={styles.grid}>{shopItems.map(shopCard)}</div>
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
              <div className={styles.grid}>
                {wardrobeItems.map(wardrobeCard)}
                {wardrobeItems.length === 0 && <div className="text-muted">Nessun oggetto in questa categoria.</div>}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
