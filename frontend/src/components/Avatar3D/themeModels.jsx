import { useRef } from "react"
import * as THREE from "three"
import { useFrame } from "@react-three/fiber"
import { Sparkles } from "@react-three/drei"
import { Ball, Box, Cone, Cyl, M, Ring, Star } from "./parts"

/*
 * Modelli 3D degli oggetti a tema (config/avatar.php -> themes).
 * Stesse coordinate di itemModels.jsx:
 *   head/face   -> centro della testa (raggio 0.3, il viso guarda +z)
 *   handR/handL -> centro della mano
 *   tutto il resto -> corpo, piedi a y = 0
 * Solo archetipi con design nostro: niente loghi o oggetti iconici di marchi.
 */

const PI = Math.PI

// Fulmine piatto (simbolo del tema Supereroi).
function Bolt({ size = 1, color = "#ffd54a", ...props }) {
  return (
    <group scale={size} {...props}>
      <Box args={[0.03, 0.09, 0.015]} radius={0.005} position={[0.012, 0.035, 0]} rotation={[0, 0, -0.45]} color={color} outline={false} />
      <Box args={[0.06, 0.022, 0.015]} radius={0.005} position={[0, 0, 0]} rotation={[0, 0, -0.2]} color={color} outline={false} />
      <Box args={[0.03, 0.09, 0.015]} radius={0.005} position={[-0.012, -0.035, 0]} rotation={[0, 0, -0.45]} color={color} outline={false} />
    </group>
  )
}

// ================================================================ Supereroi

export function MascheraEroe({ color = "#1a1f3a" }) {
  return (
    <group position={[0, 0.03, 0]}>
      {[-1, 1].map((s) => (
        <Ring key={s} r={0.062} tube={0.024} position={[s * 0.1, 0, 0.278]} scale={[1.25, 0.85, 1]} color={color} />
      ))}
      <Box args={[0.08, 0.035, 0.03]} radius={0.012} position={[0, 0.005, 0.292]} color={color} />
      <Cyl rt={0.303} h={0.06} open start={PI * 0.32} length={PI * 1.36} seg={24} color={color} side={THREE.DoubleSide} />
    </group>
  )
}

export function EmblemaEroe() {
  return (
    <group position={[0, 0.8, 0.134]}>
      <Cyl rt={0.08} h={0.012} rotation={[PI / 2, 0, 0]} color="#ffd54a" />
      <Bolt size={1.1} color="#d62828" position={[0, 0, 0.012]} />
    </group>
  )
}

export function ScudoEroe() {
  return (
    <group position={[0.06, 0.02, 0.05]} rotation={[0, 0.9, 0]}>
      <Cyl rt={0.25} h={0.04} seg={6} rotation={[PI / 2, 0, PI / 6]} color="#ffd54a" />
      <Cyl rt={0.205} h={0.045} seg={6} rotation={[PI / 2, 0, PI / 6]} position={[0, 0, 0.003]} color="#5b2fc9" />
      <Cyl rt={0.11} h={0.05} seg={6} rotation={[PI / 2, 0, PI / 6]} position={[0, 0, 0.006]} color="#12b5a8" />
      <Bolt size={1.9} color="#ffffff" position={[0, 0, 0.034]} />
    </group>
  )
}

export function MartelloTuono() {
  return (
    <group rotation={[0.8, 0, 0]}>
      <Cyl rt={0.022} h={0.36} position={[0, 0.08, 0]} color="#7a4a2a" />
      {[-0.02, 0.04, 0.1].map((y) => (
        <Cyl key={y} rt={0.026} h={0.015} position={[0, y, 0]} color="#3a2a1e" outline={false} />
      ))}
      <Box args={[0.26, 0.14, 0.14]} radius={0.03} position={[0, 0.3, 0]} color="#a7afbd" />
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.03, 0.15, 0.15]} radius={0.01} position={[s * 0.1, 0.3, 0]} color="#ffd54a" />
      ))}
      <Bolt size={0.9} color="#49d6ff" position={[0, 0.3, 0.075]} />
      <Sparkles count={10} scale={0.4} size={2.5} position={[0, 0.3, 0]} color="#9be8ff" speed={0.8} />
    </group>
  )
}

export function Robottino() {
  const bot = useRef()
  useFrame(({ clock }) => {
    if (!bot.current) return
    const t = clock.elapsedTime
    bot.current.position.y = 1.0 + Math.sin(t * 2) * 0.04
    bot.current.rotation.y = -0.4 + Math.sin(t * 0.7) * 0.3
  })

  return (
    <group ref={bot} position={[0.48, 1.0, 0.05]}>
      <Box args={[0.16, 0.13, 0.13]} radius={0.04} color="#d9dde6" />
      <Box args={[0.12, 0.05, 0.02]} radius={0.015} position={[0, 0.01, 0.065]} color="#1d2240" outline={false} />
      {[-1, 1].map((s) => (
        <Ball key={s} r={0.014} position={[s * 0.03, 0.012, 0.077]} color="#49d6ff" glow="#49d6ff" glowIntensity={1.2} outline={false} />
      ))}
      <Cyl rt={0.006} h={0.06} position={[0, 0.095, 0]} color="#8a8fa3" />
      <Ball r={0.02} position={[0, 0.13, 0]} color="#ff4f4f" glow="#ff4f4f" glowIntensity={1} />
      {[-1, 1].map((s) => (
        <Ball key={s} r={0.03} scale={[0.6, 1, 1]} position={[s * 0.095, -0.01, 0]} color="#5b2fc9" />
      ))}
      <Cone r={0.04} h={0.05} position={[0, -0.09, 0]} rotation={[PI, 0, 0]} color="#ffd54a" glow="#ffb300" glowIntensity={0.6} />
    </group>
  )
}

export function AuraEnergia() {
  const rings = useRef()
  useFrame(({ clock }) => {
    if (!rings.current) return
    const t = clock.elapsedTime
    rings.current.children.forEach((ring, i) => {
      ring.rotation.z = t * (i % 2 ? -1.2 : 1) + i
      ring.position.y = 0.35 + ((t * 0.35 + i / 3) % 1) * 1.2
    })
  })

  return (
    <group>
      <group ref={rings}>
        {[0, 1, 2].map((i) => (
          <Ring key={i} r={0.45} tube={0.012} rotation={[PI / 2, 0, 0]} color="#49d6ff" glow="#49d6ff" glowIntensity={1.4} opacity={0.75} outline={false} />
        ))}
      </group>
      <Sparkles count={25} scale={[1, 1.6, 1]} size={3} speed={0.8} position={[0, 0.85, 0]} color="#9be8ff" />
    </group>
  )
}

// Sfondo: citta' di notte.
export function SfondoCitta() {
  const buildings = [
    [-2.2, 1.6, 0.7],
    [-1.4, 2.4, 0.6],
    [-0.6, 1.4, 0.55],
    [0.5, 2.0, 0.6],
    [1.3, 2.8, 0.7],
    [2.2, 1.7, 0.6],
  ]

  return (
    <group>
      <Ball r={0.3} position={[1.9, 2.6, -3.2]} color="#fff4c9" glow="#fff1b0" glowIntensity={0.8} outline={false} />
      {buildings.map(([x, h, w]) => (
        <group key={x} position={[x, h / 2 - 0.2, -2.8]}>
          <Box args={[w, h, 0.5]} radius={0.02} color="#2b3263" outline={false} />
          {Array.from({ length: Math.floor(h / 0.35) }, (_, row) =>
            [-1, 1].map((s) => (
              <Box
                key={`${row}${s}`}
                args={[0.1, 0.12, 0.02]}
                radius={0.01}
                position={[s * w * 0.22, -h / 2 + 0.3 + row * 0.35, 0.26]}
                color={(row + s + Math.round(x)) % 3 ? "#ffe27a" : "#3e4685"}
                glow={(row + s + Math.round(x)) % 3 ? "#ffd84a" : undefined}
                glowIntensity={0.5}
                outline={false}
              />
            ))
          )}
        </group>
      ))}
      <Sparkles count={40} scale={[7, 2, 2]} size={1.6} speed={0.1} position={[0, 3, -3.4]} color="#ffffff" />
      <Cyl rt={0.6} rb={0.64} h={0.12} position={[0, -0.06, 0]} color="#4a4f6e" seg={40} />
      <Ring r={0.58} tube={0.02} rotation={[PI / 2, 0, 0]} position={[0, 0.005, 0]} color="#ffd54a" outline={false} />
    </group>
  )
}

// ================================================================ Scuola di magia

export function CappelloMago() {
  return (
    <group position={[0, 0.1, 0]} rotation={[-0.06, 0, 0.08]}>
      <Cyl rt={0.46} rb={0.46} h={0.025} seg={36} position={[0, 0.03, 0]} color="#3b2a8c" />
      <Cyl rt={0.31} rb={0.32} h={0.07} seg={32} position={[0, 0.075, 0]} color="#ffd54a" />
      <group position={[0, 0.39, 0]} rotation={[0, 0, -0.18]}>
        <Cone r={0.31} h={0.6} seg={32} color="#3b2a8c" />
      </group>
      {[
        [0.13, 0.25, 0.24, 0.035],
        [-0.12, 0.38, 0.17, 0.028],
        [0.05, 0.52, 0.1, 0.022],
      ].map(([x, y, z, s]) => (
        <Star key={y} outer={s} inner={s * 0.45} position={[x, y, z]} rotation={[-0.4, 0, 0]} color="#ffe066" glow="#ffcc00" glowIntensity={0.6} outline={false} />
      ))}
    </group>
  )
}

export function TunicaMago() {
  return (
    <group>
      <Box args={[0.045, 0.36, 0.012]} radius={0.006} position={[0, 0.74, 0.136]} color="#ffd54a" outline={false} />
      <Star outer={0.04} inner={0.018} position={[0, 0.88, 0.14]} color="#ffe066" glow="#ffcc00" glowIntensity={0.5} outline={false} />
    </group>
  )
}

export function SciarpaRighe() {
  const stripes = ["#16a3a3", "#d9dde6", "#16a3a3", "#d9dde6", "#16a3a3"]
  return (
    <group>
      <Ring r={0.15} tube={0.05} rotation={[PI / 2, 0, 0]} position={[0, 0.98, 0]} color="#16a3a3" />
      {stripes.map((c, i) => (
        <Box key={i} args={[0.08, 0.05, 0.03]} radius={0.01} position={[0.09, 0.92 - i * 0.05, 0.15]} rotation={[0.1, 0, 0.08]} color={c} />
      ))}
    </group>
  )
}

export function BacchettaMagica() {
  return (
    <group rotation={[0.8, 0, 0]}>
      <Cyl rt={0.01} rb={0.02} h={0.36} position={[0, 0.09, 0]} color="#c9a36a" />
      {[-0.04, 0.0, 0.04].map((y) => (
        <Ring key={y} r={0.02} tube={0.006} rotation={[PI / 2, 0, 0]} position={[0, y, 0]} color="#7a4a2a" outline={false} />
      ))}
      <Ball r={0.025} position={[0, 0.28, 0]} color="#5ef2e0" glow="#2fe0c8" glowIntensity={1.5} outline={false} />
      <pointLight position={[0, 0.3, 0.05]} intensity={0.25} distance={1} color="#5ef2e0" />
      <Sparkles count={16} scale={0.3} size={2.5} position={[0, 0.3, 0]} color="#9ff8ec" speed={0.7} />
    </group>
  )
}

export function ScopaVolante() {
  return (
    <group position={[0, 0.78, -0.24]} rotation={[0, 0, 0.9]}>
      <Cyl rt={0.022} h={0.95} color="#8a5a34" />
      <Ring r={0.03} tube={0.01} rotation={[PI / 2, 0, 0]} position={[0, -0.42, 0]} color="#c42828" outline={false} />
      <Cone r={0.11} h={0.3} seg={14} position={[0, -0.6, 0]} color="#e6c26a" />
    </group>
  )
}

export function Civetta() {
  const owl = useRef()
  useFrame(({ clock }) => {
    if (!owl.current) return
    const t = clock.elapsedTime
    owl.current.rotation.y = Math.sin(t * 0.8) * 0.5
    owl.current.position.y = 1.07 + Math.abs(Math.sin(t * 2.5)) * 0.01
  })

  return (
    <group ref={owl} position={[-0.44, 1.07, 0.04]}>
      <Ball r={0.1} scale={[1, 1.15, 0.95]} color="#f4f4f6" />
      {[[-0.04, 0.0], [0.05, -0.05], [-0.02, -0.07]].map(([x, y]) => (
        <Ball key={`${x}${y}`} r={0.012} position={[x, y, 0.09]} color="#8a8fa3" outline={false} />
      ))}
      {[-1, 1].map((s) => (
        <group key={s}>
          <Ball r={0.035} position={[s * 0.04, 0.05, 0.08]} color="#ffd54a" />
          <Ball r={0.018} position={[s * 0.04, 0.05, 0.105]} color="#1d1b2e" outline={false} />
          <Ball r={0.05} scale={[0.4, 1, 0.8]} position={[s * 0.095, -0.02, 0]} color="#dfe3ea" />
        </group>
      ))}
      <Cone r={0.016} h={0.035} position={[0, 0.015, 0.1]} rotation={[PI, 0, 0]} color="#3a3a4a" />
    </group>
  )
}

export function AuraRune() {
  const ring = useRef()
  useFrame(({ clock }) => {
    if (ring.current) ring.current.rotation.y = clock.elapsedTime * 0.6
  })

  return (
    <group>
      <group ref={ring} position={[0, 0.9, 0]}>
        {Array.from({ length: 6 }, (_, i) => {
          const a = (i / 6) * PI * 2
          return (
            <Star
              key={i}
              outer={0.05}
              inner={0.022}
              position={[Math.sin(a) * 0.6, Math.sin(i * 1.7) * 0.25, Math.cos(a) * 0.6]}
              rotation={[0, a, 0]}
              color="#c77dff"
              glow="#b05cff"
              glowIntensity={1.4}
              outline={false}
            />
          )
        })}
      </group>
      <Sparkles count={35} scale={[1.1, 1.7, 1.1]} size={3} speed={0.4} position={[0, 0.85, 0]} color="#d9b3ff" />
    </group>
  )
}

// Sfondo: torre dei maghi di notte.
export function SfondoTorre() {
  return (
    <group>
      <Ball r={0.32} position={[1.7, 2.5, -3.2]} color="#f4f0ff" glow="#e6dcff" glowIntensity={0.7} outline={false} />
      <group position={[-1.6, 0, -2.6]}>
        <Cyl rt={0.42} rb={0.5} h={2.4} seg={20} position={[0, 1.0, 0]} color="#5a5478" outline={false} />
        <Cone r={0.6} h={0.9} seg={20} position={[0, 2.65, 0]} color="#5b2a86" outline={false} />
        {[0.6, 1.2, 1.8].map((y) => (
          <Box key={y} args={[0.14, 0.2, 0.05]} radius={0.04} position={[0, y, 0.46]} color="#ffe27a" glow="#ffd84a" glowIntensity={0.6} outline={false} />
        ))}
        <Star outer={0.09} inner={0.04} position={[0, 3.15, 0]} color="#ffe066" glow="#ffcc00" glowIntensity={1} outline={false} />
      </group>
      <Cyl rt={0.3} rb={0.36} h={1.4} seg={16} position={[2.2, 0.5, -3]} color="#4a4566" outline={false} />
      <Cone r={0.42} h={0.6} seg={16} position={[2.2, 1.5, -3]} color="#3b2a8c" outline={false} />
      <Sparkles count={60} scale={[7, 3, 2]} size={1.8} speed={0.15} position={[0, 2.4, -3.2]} color="#ffffff" />
      <Cyl rt={0.6} rb={0.64} h={0.12} position={[0, -0.06, 0]} color="#6d6a86" seg={40} />
      <M position={[0, 0.005, 0]} rotation={[-PI / 2, 0, 0]} color="#c77dff" glow="#b05cff" glowIntensity={0.6} opacity={0.6} outline={false}>
        <ringGeometry args={[0.45, 0.5, 40]} />
      </M>
    </group>
  )
}
