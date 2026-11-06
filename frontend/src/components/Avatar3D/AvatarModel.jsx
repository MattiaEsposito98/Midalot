import { useRef } from "react"
import { useFrame } from "@react-three/fiber"
import { Ball, Box, Cap, Cone, Cyl, M, Ring } from "./parts"
import { ITEM_MODELS } from "./items"
import { BASE_ITEMS, DEFAULT_AVATAR } from "./options"
import { faceZ, headGeometry, radiusAt } from "./head"

const PI = Math.PI

// Riccioli: palline sparse sulla calotta, tranne davanti dove c'e' il viso.
const CURLS = (() => {
  const out = []
  for (let ring = 0; ring < 4; ring++) {
    const theta = 0.15 + ring * 0.38 // 0 = cima della testa
    const count = ring === 0 ? 4 : 9 + ring * 2
    for (let i = 0; i < count; i++) {
      const phi = (i / count) * PI * 2 + ring * 0.3 // 0 = davanti
      if (Math.cos(phi) > 0.45 && theta > 0.8) continue
      const r = 0.315
      out.push([r * Math.sin(theta) * Math.sin(phi), r * Math.cos(theta), r * Math.sin(theta) * Math.cos(phi)])
    }
  }
  return out
})()

// Afro: nuvola di riccioli grandi intorno alla testa, lasciando libero il viso.
const AFRO = (() => {
  const out = []
  for (let ring = 0; ring < 5; ring++) {
    const theta = 0.05 + ring * 0.42
    const count = ring === 0 ? 1 : 7 + ring * 2
    for (let i = 0; i < count; i++) {
      const phi = (i / count) * PI * 2 + ring * 0.4
      if (Math.cos(phi) > 0.3 && theta > 0.75) continue
      const r = 0.36
      out.push([r * Math.sin(theta) * Math.sin(phi), 0.06 + r * Math.cos(theta), -0.04 + r * Math.sin(theta) * Math.cos(phi)])
    }
  }
  return out
})()

function Hair({ style, color, hidden }) {
  if (style === "rasato") return null

  // Calotta di base (dietro e sopra), coperta da cappelli ed elmi che nascondono i capelli.
  const cap = !hidden && style !== "cresta" && (
    <Cap r={0.318} theta={PI * 0.42} rotation={[-0.38, 0, 0]} position={[0, 0.005, -0.01]} color={color} />
  )
  const sideburns = [-1, 1].map((s) => (
    <Box key={s} args={[0.04, 0.12, 0.06]} radius={0.02} position={[s * 0.29, -0.02, 0.06]} color={color} />
  ))

  return (
    <group>
      {cap}

      {!hidden && style === "corto" && (
        <>
          <Ball r={0.07} scale={[1.4, 0.7, 1]} position={[-0.07, 0.2, 0.2]} rotation={[0, 0, 0.3]} color={color} />
          {sideburns}
        </>
      )}

      {!hidden && style === "riga" && (
        <>
          <Box args={[0.38, 0.09, 0.17]} radius={0.045} position={[0.05, 0.235, 0.16]} rotation={[0.55, 0.1, -0.38]} color={color} />
          <Ball r={0.06} scale={[1.6, 0.6, 1]} position={[-0.15, 0.2, 0.18]} rotation={[0.4, 0, 0.5]} color={color} />
          {sideburns}
        </>
      )}

      {!hidden &&
        style === "spettinato" &&
        [
          [0, 0.31, 0.02, 0, 0],
          [0.12, 0.28, 0.06, 0, -0.5],
          [-0.12, 0.28, 0.06, 0, 0.5],
          [0.07, 0.27, -0.13, -0.5, -0.3],
          [-0.07, 0.27, -0.13, -0.5, 0.3],
          [0, 0.24, 0.19, 0.6, 0],
        ].map(([x, y, z, rx, rz], i) => <Cone key={i} r={0.075} h={0.16} position={[x, y, z]} rotation={[rx, 0, rz]} color={color} />)}

      {!hidden && style === "ricci" && CURLS.map(([x, y, z], i) => <Ball key={i} r={0.07} seg={12} position={[x, y, z]} color={color} />)}

      {!hidden && style === "cresta" && (
        <>
          {/* testa rasata ai lati, cresta al centro */}
          <Cap r={0.304} theta={PI * 0.4} rotation={[-0.35, 0, 0]} color={color} opacity={0.6} outline={false} />
          {/* striscia continua dalla fronte alla nuca */}
          <group rotation={[0, PI / 2, 0]}>
            <Ring r={0.32} tube={0.055} arc={2.45} rotation={[0, 0, 0.35]} color={color} />
          </group>
          {[-0.9, -0.45, 0, 0.45, 0.9].map((a, i) => (
            <Cone
              key={i}
              r={0.055}
              h={0.17 - Math.abs(a) * 0.05}
              position={[0, Math.cos(a) * 0.36 + 0.02, Math.sin(a) * 0.36]}
              rotation={[a, 0, 0]}
              color={color}
            />
          ))}
        </>
      )}

      {!hidden && style === "afro" && AFRO.map(([x, y, z], i) => <Ball key={i} r={0.13} seg={14} position={[x, y, z]} color={color} />)}

      {!hidden && style === "caschetto" && (
        <Cap r={0.335} theta={PI * 0.62} phiStart={PI / 2 + 0.8} phiLength={PI * 2 - 1.6} position={[0, -0.01, -0.01]} color={color} />
      )}

      {style === "lungo" && (
        <>
          <Box args={[0.54, 0.56, 0.16]} radius={0.075} position={[0, -0.22, -0.17]} color={color} />
          {[-1, 1].map((s) => (
            <Box key={s} args={[0.1, 0.5, 0.15]} radius={0.05} position={[s * 0.27, -0.19, -0.01]} color={color} />
          ))}
        </>
      )}

      {style === "coda" && (
        <group position={[0, 0.05, -0.32]} rotation={[0.5, 0, 0]}>
          <Ring r={0.04} tube={0.018} rotation={[PI / 2, 0, 0]} color="#e23b3b" />
          <Ball r={0.11} scale={[0.85, 1.6, 0.85]} position={[0, -0.16, -0.02]} color={color} />
        </group>
      )}

      {style === "codini" &&
        [-1, 1].map((s) => (
          <group key={s} position={[s * 0.3, 0.04, -0.08]} rotation={[0.2, 0, s * 0.35]}>
            <Ring r={0.035} tube={0.016} rotation={[PI / 2, 0, 0]} color="#ff5fa2" />
            <Ball r={0.085} scale={[0.85, 1.5, 0.85]} position={[0, -0.12, 0]} color={color} />
          </group>
        ))}

      {style === "chignon" && (
        <group position={[0, 0.25, -0.2]}>
          <Ball r={0.12} color={color} />
          <Ring r={0.1} tube={0.016} rotation={[1.1, 0, 0]} position={[0, -0.03, 0.03]} color="#6c4bd8" />
        </group>
      )}

      {style === "treccia" &&
        [0, 1, 2, 3, 4, 5].map((i) => (
          <Ball
            key={i}
            r={0.075 - i * 0.006}
            scale={[1, 0.85, 0.85]}
            position={[(i % 2 ? 0.015 : -0.015), -0.05 - i * 0.095, -0.31 + i * 0.02]}
            color={color}
          />
        ))}
      {style === "treccia" && <Ring r={0.03} tube={0.014} rotation={[PI / 2, 0, 0]} position={[0, -0.6, -0.2]} color="#e23b3b" />}
    </group>
  )
}

function Brows({ style, color, shape }) {
  const y = 0.105
  return [-1, 1].map((s) => {
    const x = s * 0.105
    const z = faceZ(shape, x, y) + 0.004

    if (style === "arcuate") {
      return <Ring key={s} r={0.05} tube={0.01} arc={1.5} position={[x, y - 0.035, z]} rotation={[0, 0, PI / 2 - 0.75]} color={color} outline={false} />
    }

    const size = { morbide: [0.085, 0.022], folte: [0.1, 0.036], sottili: [0.08, 0.013] }[style] || [0.085, 0.022]
    return <Box key={s} args={[size[0], size[1], 0.018]} radius={size[1] / 2.2} position={[x, y, z]} rotation={[0, s * 0.25, s * -0.12]} color={color} outline={false} />
  })
}

function Mouth({ style, shape, lift = 0 }) {
  const y = -0.155
  const z = faceZ(shape, 0, y) + 0.002 + lift

  if (style === "sorrisone") {
    return (
      <group position={[0, y + 0.01, z]}>
        <M rotation={[0, 0, PI]} color="#8a2c2c" outline={false}>
          <circleGeometry args={[0.052, 24, 0, PI]} />
        </M>
        <Box args={[0.075, 0.016, 0.006]} radius={0.004} position={[0, -0.009, 0.002]} color="#ffffff" outline={false} />
      </group>
    )
  }
  if (style === "neutro") {
    return <Box args={[0.06, 0.012, 0.01]} radius={0.005} position={[0, y, z]} color="#7a2f2f" outline={false} />
  }
  if (style === "furbetto") {
    return <Ring r={0.045} tube={0.011} arc={PI * 0.65} rotation={[0, 0, PI + 0.55]} position={[0.012, y + 0.012, z]} color="#7a2f2f" outline={false} />
  }
  return <Ring r={0.045} tube={0.011} arc={PI} rotation={[0, 0, PI]} position={[0, y + 0.02, z]} color="#7a2f2f" outline={false} />
}

function Beard({ style, color, shape }) {
  if (!style || style === "nessuna") return null

  const mustache = [-1, 1].map((s) => (
    <Ball
      key={s}
      r={0.04}
      scale={[1.3, 0.48, 0.6]}
      position={[s * 0.034, -0.112, faceZ(shape, s * 0.034, -0.112) + 0.004]}
      rotation={[0, 0, s * 0.28]}
      color={color}
      outline={false}
    />
  ))

  return (
    <group>
      {mustache}
      {style === "pizzetto" && <Ball r={0.045} scale={[1, 1.15, 0.6]} position={[0, -0.265, faceZ(shape, 0, -0.265) - 0.005]} color={color} />}
      {(style === "corta" || style === "folta") && (
        <M color={color}>
          <primitive
            attach="geometry"
            object={headGeometry(shape, {
              yMax: style === "folta" ? -0.1 : -0.15,
              grow: style === "folta" ? 0.03 : 0.012,
              phiStart: -1.65,
              phiLength: 3.3,
            })}
          />
        </M>
      )}
    </group>
  )
}

function Face({ cfg, eyesRef }) {
  const shape = cfg.faceShape
  const beard = cfg.beard && cfg.beard !== "nessuna"
  const mouthLift = cfg.beard === "folta" ? 0.03 : cfg.beard === "corta" ? 0.013 : 0

  return (
    <group>
      <group ref={eyesRef}>
        {[-1, 1].map((s) => (
          <group key={s} position={[s * 0.105, 0.0, faceZ(shape, s * 0.105, 0.0) - 0.016]} rotation={[0, s * 0.3, 0]}>
            {/* bianco dell'occhio, iride colorata, pupilla e riflesso */}
            <Ball r={0.06} scale={[0.85, 1.05, 0.42]} color="#ffffff" outline={false} />
            <Ball r={0.04} scale={[0.85, 1, 0.4]} position={[0, -0.006, 0.012]} color={cfg.eyes} outline={false} />
            <Ball r={0.02} scale={[1, 1, 0.4]} position={[0, -0.006, 0.019]} color="#14121f" outline={false} />
            <Ball r={0.011} position={[0.012, 0.012, 0.024]} color="#ffffff" outline={false} />
            {/* contorno superiore (palpebra) */}
            <Ring r={0.05} tube={0.007} arc={PI * 0.8} position={[0, 0.0, 0.012]} rotation={[0, 0, PI * 0.1]} scale={[1.02, 1.25, 1]} color="#1d1b2e" outline={false} />
            {cfg.gender === "f" && (
              <Box args={[0.018, 0.04, 0.01]} radius={0.004} position={[s * 0.052, 0.05, 0.008]} rotation={[0, 0, s * -0.75]} color="#1d1b2e" outline={false} />
            )}
          </group>
        ))}
      </group>

      <Brows style={cfg.brows} color={cfg.hairColor} shape={shape} />

      <Ball r={0.026} scale={[1, 0.85, 0.9]} position={[0, -0.065, faceZ(shape, 0, -0.065) + 0.004]} color={cfg.skin} />

      <Mouth style={cfg.mouth} shape={shape} lift={mouthLift} />

      {!beard &&
        [-1, 1].map((s) => (
          <Ball
            key={s}
            r={0.042}
            scale={[1, 0.6, 0.3]}
            position={[s * 0.17, -0.1, faceZ(shape, s * 0.17, -0.1) - 0.006]}
            rotation={[0, s * 0.6, 0]}
            color="#ff9a9a"
            opacity={0.5}
            outline={false}
          />
        ))}

      {cfg.freckles &&
        [-1, 1].flatMap((s) =>
          [
            [0.13, -0.045],
            [0.16, -0.065],
            [0.185, -0.04],
            [0.145, -0.08],
          ].map(([x, y], i) => (
            <Ball key={`${s}${i}`} r={0.008} scale={[1, 1, 0.3]} position={[s * x, y, faceZ(shape, s * x, y)]} color="#b0683f" outline={false} />
          ))
        )}

      <Beard style={cfg.beard} color={cfg.hairColor} shape={shape} />
    </group>
  )
}

/**
 * Personaggio completo. config: vedi DEFAULT_AVATAR.
 * animate=false lo lascia fermo (immagini del profilo).
 */
export default function AvatarModel({ config, animate = true, ...props }) {
  const root = useRef()
  const head = useRef()
  const armL = useRef()
  const armR = useRef()
  const eyesRef = useRef()

  const cfg = { ...DEFAULT_AVATAR, ...config }
  const equipped = { ...BASE_ITEMS, ...(cfg.equipped || {}) }
  const item = (slot) => (equipped[slot] ? ITEM_MODELS[equipped[slot]] : null)

  const female = cfg.gender === "f"
  const skin = cfg.skin
  const shirt = item("torso")?.look?.shirt || "#f4f4f6"
  const legsLook = item("legs")?.look || {}
  const pants = legsLook.pants || "#3d5f9e"
  const feet = item("feet")?.look || { shoe: "#ffffff", sole: "#e23b3b" }
  const hands = item("hands")?.look?.gloves || skin
  const headItem = item("head")
  const holdR = !!item("handR")
  const holdL = !!item("handL")

  const torsoW = female ? 0.36 : 0.4
  const shoulderX = torsoW / 2 + 0.045
  const earY = -0.03
  const earX = radiusAt(cfg.faceShape, earY) - 0.01

  useFrame(({ clock }) => {
    if (!animate) return
    const t = clock.elapsedTime

    if (root.current) root.current.position.y = Math.sin(t * 2) * 0.008
    if (head.current) {
      head.current.rotation.y = Math.sin(t * 0.6) * 0.12
      head.current.rotation.z = Math.sin(t * 0.9) * 0.035
    }
    if (armL.current) armL.current.rotation.z = 0.13 + Math.sin(t * 2) * 0.025
    if (armR.current) armR.current.rotation.z = -0.13 - Math.sin(t * 2) * 0.025

    // Battito di ciglia ogni ~4 secondi.
    if (eyesRef.current) eyesRef.current.scale.y = t % 4 < 0.12 ? 0.15 : 1
  })

  const Model = (slot) => {
    const it = item(slot)
    if (!it?.Model) return null
    const C = it.Model
    return <C emblem={cfg.emblem} body={{ torsoW, female }} />
  }

  return (
    <group {...props}>
      <group ref={root}>
        {/* Piedi e gambe */}
        {[-1, 1].map((s) => (
          <group key={s} position={[s * 0.1, 0, 0]}>
            <Box args={[0.15, 0.1, 0.25]} radius={0.045} position={[0, 0.06, 0.025]} color={feet.shoe} />
            <Box args={[0.155, 0.03, 0.255]} radius={0.012} position={[0, 0.015, 0.025]} color={feet.sole} />
            {feet.tall && <Cyl rt={0.085} rb={0.08} h={0.14} position={[0, 0.15, 0]} color={feet.shoe} />}
            {legsLook.shorts || legsLook.skirt ? (
              <>
                <Cyl rt={0.066} rb={0.062} h={0.3} position={[0, 0.3, 0]} color={skin} />
                {legsLook.shorts && <Cyl rt={0.082} rb={0.08} h={0.13} position={[0, 0.42, 0]} color={pants} />}
              </>
            ) : (
              <Cyl rt={0.078} rb={0.072} h={0.3} position={[0, 0.3, 0]} color={pants} />
            )}
          </group>
        ))}

        {/* Bacino e busto */}
        <Box args={[torsoW - 0.03, 0.16, 0.24]} radius={0.07} position={[0, 0.52, 0]} color={pants} />
        {legsLook.skirt && <Cyl rt={0.18} rb={0.27} h={0.22} seg={24} position={[0, 0.48, 0]} color={pants} />}
        <Box args={[torsoW, 0.37, female ? 0.25 : 0.26]} radius={0.1} position={[0, 0.77, 0]} color={shirt} />
        <Cyl rt={0.056} h={0.14} position={[0, 0.99, 0]} color={skin} />

        {/* Braccia: la mano ha un punto di aggancio per gli oggetti */}
        {[
          [1, armL, holdL, "handL"],
          [-1, armR, holdR, "handR"],
        ].map(([s, ref, hold, slot]) => (
          <group key={slot} position={[s * shoulderX, 0.91, 0]}>
            <group ref={ref} rotation={[hold ? -0.35 : 0, 0, s * 0.13]}>
              <Ball r={0.07} color={shirt} />
              <Cyl rt={0.066} rb={0.06} h={0.14} position={[0, -0.08, 0]} color={shirt} />
              <Cyl rt={0.055} rb={0.052} h={0.16} position={[0, -0.22, 0]} color={skin} />
              <group position={[0, -0.34, 0]}>
                <Ball r={0.07} color={hands} />
                <group position={[0, -0.01, 0]}>{Model(slot)}</group>
              </group>
            </group>
          </group>
        ))}

        {/* Vestiti e accessori del corpo */}
        {Model("torso")}
        {Model("armor")}
        {Model("neck")}
        {Model("legs")}
        {Model("back")}
        {Model("pet")}
        {Model("aura")}

        {/* Testa: calotta tonda sopra, guance e mento sotto (forma scelta dall'utente) */}
        <group ref={head} position={[0, 1.31, 0]}>
          <M color={skin}>
            <primitive attach="geometry" object={headGeometry(cfg.faceShape)} />
          </M>
          {[-1, 1].map((s) => (
            <Ball key={s} r={0.055} scale={[0.6, 1, 0.8]} position={[s * earX, earY, 0.0]} color={skin} />
          ))}
          <Face cfg={cfg} eyesRef={eyesRef} />
          <Hair style={cfg.hair} color={cfg.hairColor} hidden={!!headItem?.hidesHair} />
          {Model("head")}
          {Model("face")}
        </group>
      </group>

      {/* Ombra a terra */}
      <M position={[0, 0.002, 0]} rotation={[-PI / 2, 0, 0]} color="#000000" opacity={0.18} outline={false}>
        <circleGeometry args={[0.38, 32]} />
      </M>
    </group>
  )
}
