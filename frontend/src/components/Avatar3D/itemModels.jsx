import { useRef } from "react"
import * as THREE from "three"
import { useFrame } from "@react-three/fiber"
import { Sparkles } from "@react-three/drei"
import { Ball, Box, Cap, Cone, Cyl, Heart, M, Ring, Star } from "./parts"

/*
 * Modelli 3D degli oggetti dell'avatar. Coordinate:
 *   head/face   -> centro della testa (raggio 0.3, il viso guarda +z)
 *   handR/handL -> centro della mano
 *   tutto il resto -> corpo, piedi a y = 0
 */

const PI = Math.PI

// ---------------------------------------------------------------- testa

export function Cappellino({ color = "#e23b3b", visor = "#c42828" }) {
  return (
    <group position={[0, 0.03, 0]} rotation={[-0.12, 0, 0]}>
      <Cap r={0.318} theta={PI * 0.4} color={color} />
      <Cyl rt={0.2} h={0.022} start={-PI / 2} length={PI} position={[0, 0.13, 0.2]} rotation={[0.18, 0, 0]} color={visor} />
      <Ball r={0.03} position={[0, 0.315, 0]} color="#ffffff" />
    </group>
  )
}

export function ToccoLaureato() {
  const tassel = useRef()
  useFrame(({ clock }) => {
    if (tassel.current) tassel.current.rotation.z = Math.sin(clock.elapsedTime * 2.2) * 0.15
  })

  return (
    <group position={[0, 0.02, 0]} rotation={[-0.08, 0, 0]}>
      <Cyl rt={0.25} rb={0.27} h={0.14} position={[0, 0.24, 0]} color="#22223a" />
      <Box args={[0.58, 0.035, 0.58]} radius={0.01} position={[0, 0.325, 0]} rotation={[0, PI / 4, 0]} color="#2b2b48" />
      <Ball r={0.025} position={[0, 0.35, 0]} color="#f2c230" />
      <group ref={tassel} position={[0.29, 0.33, 0.12]}>
        <Box args={[0.02, 0.16, 0.02]} radius={0.008} position={[0, -0.08, 0]} color="#f2c230" />
        <Ball r={0.025} position={[0, -0.17, 0]} color="#f2c230" />
      </group>
    </group>
  )
}

export function CappelloEsploratore() {
  return (
    <group position={[0, 0.03, 0]} rotation={[-0.1, 0, 0]}>
      <Cyl rt={0.46} h={0.025} position={[0, 0.16, 0]} color="#c9a96e" />
      <Cyl rt={0.23} rb={0.29} h={0.2} position={[0, 0.27, 0]} color="#d4b77d" />
      <Cyl rt={0.292} h={0.05} position={[0, 0.2, 0]} color="#6b4a2b" />
    </group>
  )
}

export function CappelloSapiente() {
  return (
    <group position={[0, 0.03, 0]} rotation={[-0.08, 0, 0]}>
      <Cyl rt={0.48} h={0.025} position={[0, 0.16, 0]} color="#2a2f7a" />
      <Cone r={0.3} h={0.62} position={[0, 0.47, -0.03]} rotation={[-0.18, 0, 0.12]} color="#343b96" />
      <Star outer={0.06} inner={0.026} position={[0.08, 0.33, 0.23]} rotation={[-0.3, 0.2, 0]} color="#ffd54a" glow="#ffb300" glowIntensity={0.5} />
      <Star outer={0.04} inner={0.018} position={[-0.1, 0.5, 0.15]} rotation={[-0.3, -0.3, 0]} color="#ffd54a" glow="#ffb300" glowIntensity={0.5} />
    </group>
  )
}

export function Elmo({ gold = false }) {
  const metal = gold ? "#f2c230" : "#aab3c2"
  const dark = gold ? "#c9951a" : "#7c8697"

  return (
    <group position={[0, 0.02, 0]}>
      <Cap r={0.34} theta={PI * 0.5} color={metal} />
      <Ring r={0.335} tube={0.025} rotation={[PI / 2, 0, 0]} position={[0, 0.02, 0]} color={dark} />
      <Box args={[0.05, 0.2, 0.04]} radius={0.015} position={[0, 0.0, 0.335]} color={dark} />
      {gold && (
        <>
          {[0, 1, 2, 3, 4].map((i) => (
            <Ball key={i} r={0.07 - i * 0.004} position={[0, 0.33 + Math.sin(i * 0.5) * 0.06, 0.12 - i * 0.08]} color="#e23b3b" />
          ))}
          <Ball r={0.03} position={[0, 0.16, 0.31]} color="#3dd6ff" glow="#3dd6ff" glowIntensity={0.6} />
        </>
      )}
    </group>
  )
}

export function CoronaAlloro() {
  return (
    <group position={[0, 0.17, 0]} rotation={[-0.1, 0, 0]}>
      <Ring r={0.29} tube={0.018} rotation={[PI / 2, 0, 0]} color="#5f8f2f" />
      {Array.from({ length: 14 }).map((_, i) => {
        const a = (i / 14) * PI * 2
        if (Math.abs(Math.sin(a)) > 0.93 && Math.cos(a) > 0) return null
        return (
          <Ball
            key={i}
            r={0.05}
            position={[Math.sin(a) * 0.3, 0.02, Math.cos(a) * 0.3]}
            rotation={[0, a, 0.6]}
            scale={[0.5, 1, 0.25]}
            color={i % 2 ? "#7cb342" : "#689f38"}
          />
        )
      })}
    </group>
  )
}

export function CoronaLeggenda() {
  return (
    <group position={[0, 0.28, 0]} rotation={[-0.08, 0, 0]}>
      <Cyl rt={0.25} rb={0.24} h={0.12} open color="#f2c230" />
      {Array.from({ length: 6 }).map((_, i) => {
        const a = (i / 6) * PI * 2
        return (
          <group key={i} position={[Math.sin(a) * 0.245, 0.1, Math.cos(a) * 0.245]}>
            <Cone r={0.045} h={0.12} seg={4} color="#f2c230" />
            <Ball r={0.022} position={[0, 0.07, 0]} color="#fff3b0" />
          </group>
        )
      })}
      <Ball r={0.035} position={[0, 0, 0.25]} color="#e5303f" glow="#e5303f" glowIntensity={0.5} />
      <Ball r={0.03} position={[0.18, 0, 0.17]} color="#3dd6ff" glow="#3dd6ff" glowIntensity={0.5} />
      <Ball r={0.03} position={[-0.18, 0, 0.17]} color="#4cd964" glow="#4cd964" glowIntensity={0.5} />
    </group>
  )
}

// ---------------------------------------------------------------- viso

export function OcchialiTondi() {
  return (
    <group position={[0, 0.02, 0.29]}>
      {[-1, 1].map((s) => (
        <group key={s} position={[s * 0.1, 0, 0]}>
          <Ring r={0.065} tube={0.012} color="#2d2d3a" />
          <M position={[0, 0, -0.005]} color="#bfe8ff" opacity={0.35} outline={false}>
            <circleGeometry args={[0.062, 24]} />
          </M>
        </group>
      ))}
      <Box args={[0.07, 0.014, 0.014]} radius={0.006} color="#2d2d3a" />
    </group>
  )
}

export function OcchialiAviatore() {
  return (
    <group position={[0, 0.03, 0.285]}>
      {[-1, 1].map((s) => (
        <group key={s} position={[s * 0.105, 0, 0]}>
          <Ball r={0.075} scale={[1, 0.82, 0.3]} color="#3b2f5c" glow="#6d5bd0" glowIntensity={0.25} />
          <Ring r={0.075} tube={0.011} scale={[1, 0.82, 1]} color="#d9a520" />
        </group>
      ))}
      <Box args={[0.06, 0.014, 0.014]} radius={0.006} position={[0, 0.035, 0]} color="#d9a520" />
    </group>
  )
}

export function OcchialiMezzaluna() {
  return (
    <group position={[0, -0.025, 0.293]}>
      {[-1, 1].map((s) => (
        <Ring key={s} r={0.055} tube={0.01} arc={PI} rotation={[0, 0, PI]} position={[s * 0.09, 0.0, 0]} color="#d9a520" />
      ))}
      <Box args={[0.07, 0.012, 0.012]} radius={0.005} color="#d9a520" />
    </group>
  )
}

// ---------------------------------------------------------------- busto (dettagli sopra la maglia)

export function LogoMidalot() {
  return (
    <group position={[0, 0.8, 0.134]}>
      <Cyl rt={0.075} h={0.012} rotation={[PI / 2, 0, 0]} color="#ffd54a" />
      <Box args={[0.02, 0.075, 0.012]} radius={0.004} position={[-0.03, 0, 0.01]} color="#3a2a8c" outline={false} />
      <Box args={[0.02, 0.075, 0.012]} radius={0.004} position={[0.03, 0, 0.01]} color="#3a2a8c" outline={false} />
      <Box args={[0.02, 0.05, 0.012]} radius={0.004} position={[-0.012, 0.012, 0.012]} rotation={[0, 0, 0.6]} color="#3a2a8c" outline={false} />
      <Box args={[0.02, 0.05, 0.012]} radius={0.004} position={[0.012, 0.012, 0.012]} rotation={[0, 0, -0.6]} color="#3a2a8c" outline={false} />
    </group>
  )
}

export function Camicia() {
  return (
    <group>
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.1, 0.06, 0.02]} radius={0.008} position={[s * 0.055, 0.93, 0.125]} rotation={[0, 0, s * -0.5]} color="#ffffff" />
      ))}
      {[0.85, 0.77, 0.69].map((y) => (
        <Ball key={y} r={0.012} position={[0, y, 0.133]} color="#3c4f7a" outline={false} />
      ))}
    </group>
  )
}

export function Gilet() {
  return (
    <group position={[0, 0.77, 0]}>
      {[-1, 1].map((s) => (
        <group key={s}>
          <Box args={[0.17, 0.35, 0.285]} radius={0.06} position={[s * 0.118, 0, 0]} color="#8a7a4a" />
          <Box args={[0.08, 0.07, 0.02]} radius={0.01} position={[s * 0.11, -0.07, 0.145]} color="#6f6038" />
        </group>
      ))}
    </group>
  )
}

// ---------------------------------------------------------------- armature

export function Armatura({ main, dark, gem }) {
  return (
    <group>
      <Box args={[0.43, 0.3, 0.29]} radius={0.09} position={[0, 0.8, 0]} color={main} />
      <Box args={[0.38, 0.06, 0.27]} radius={0.025} position={[0, 0.62, 0]} color={dark} />
      <Box args={[0.07, 0.05, 0.02]} radius={0.01} position={[0, 0.62, 0.14]} color="#f2c230" />
      {[-1, 1].map((s) => (
        <Cap key={s} r={0.1} theta={PI * 0.5} position={[s * 0.24, 0.91, 0]} rotation={[0, 0, s * -0.5]} color={dark} />
      ))}
      {gem && <Ball r={0.035} position={[0, 0.83, 0.15]} color={gem} glow={gem} glowIntensity={0.7} />}
    </group>
  )
}

// ---------------------------------------------------------------- collo e mani

export function Sciarpa() {
  return (
    <group>
      <Ring r={0.11} tube={0.05} rotation={[PI / 2, 0, 0]} position={[0, 0.96, 0]} color="#e23b3b" />
      <Box args={[0.08, 0.22, 0.04]} radius={0.02} position={[0.08, 0.84, 0.15]} rotation={[0.15, 0, 0.12]} color="#e23b3b" />
      <Box args={[0.081, 0.04, 0.042]} radius={0.01} position={[0.088, 0.77, 0.16]} rotation={[0.15, 0, 0.12]} color="#ffffff" />
    </group>
  )
}

// ---------------------------------------------------------------- gambe e piedi (dettagli)

export function TascheCargo() {
  return [-1, 1].map((s) => <Box key={s} args={[0.03, 0.09, 0.09]} radius={0.01} position={[s * 0.178, 0.33, 0]} color="#76663d" />)
}

export function Gambali() {
  return [-1, 1].map((s) => (
    <group key={s}>
      <Ball r={0.065} position={[s * 0.1, 0.3, 0.05]} color="#aab3c2" />
      <Box args={[0.12, 0.14, 0.04]} radius={0.015} position={[s * 0.1, 0.18, 0.065]} color="#8b95a6" />
    </group>
  ))
}

// ---------------------------------------------------------------- mano destra

export function Matita() {
  return (
    <group rotation={[0.7, 0, 0]} position={[0, 0.02, 0]}>
      <Cyl rt={0.028} h={0.36} seg={6} position={[0, 0.08, 0]} color="#ffcc33" />
      <Cone r={0.028} h={0.07} seg={6} position={[0, 0.295, 0]} color="#f3d9a6" />
      <Cone r={0.01} h={0.025} seg={6} position={[0, 0.322, 0]} color="#333333" />
      <Cyl rt={0.03} h={0.04} position={[0, -0.12, 0]} color="#c0c0c0" />
      <Cyl rt={0.029} h={0.04} position={[0, -0.16, 0]} color="#ff8fa3" />
    </group>
  )
}

export function Torcia() {
  return (
    <group rotation={[1.25, 0, 0]}>
      <Cyl rt={0.032} h={0.24} position={[0, 0.03, 0]} color="#4a4a5a" />
      <Cyl rt={0.055} rb={0.035} h={0.07} position={[0, 0.18, 0]} color="#6a6a7c" />
      <M position={[0, 0.216, 0]} rotation={[-PI / 2, 0, 0]} color="#fff6c2" glow="#fff1a0" glowIntensity={1.4} outline={false}>
        <circleGeometry args={[0.05, 20]} />
      </M>
      <M position={[0, 0.5, 0]} color="#fff4b0" opacity={0.18} outline={false}>
        <coneGeometry args={[0.2, 0.56, 24, 1, true]} />
      </M>
      <pointLight position={[0, 0.45, 0]} intensity={0.3} distance={1.5} color="#fff1c0" />
    </group>
  )
}

export function PennaOca() {
  return (
    <group rotation={[0.5, 0, -0.2]}>
      <Cyl rt={0.008} rb={0.004} h={0.22} position={[0, 0.06, 0]} color="#f5e6c8" />
      <Ball r={0.1} scale={[0.35, 1.1, 0.08]} position={[0.012, 0.2, 0]} rotation={[0, 0, -0.15]} color="#ffffff" />
    </group>
  )
}

export function Spada({ blade, guard, grip, length = 0.55, glow }) {
  return (
    <group rotation={[0.9, 0, 0]}>
      <Cyl rt={0.022} h={0.13} position={[0, 0, 0]} color={grip} />
      <Ball r={0.032} position={[0, -0.075, 0]} color={guard} />
      <Box args={[0.2, 0.035, 0.05]} radius={0.015} position={[0, 0.08, 0]} color={guard} />
      <Box args={[0.065, length, 0.02]} radius={0.008} position={[0, 0.1 + length / 2, 0]} color={blade} glow={glow} glowIntensity={0.6} opacity={glow ? 0.9 : undefined} />
      <Cone r={0.046} h={0.08} seg={4} position={[0, 0.14 + length, 0]} rotation={[0, PI / 4, 0]} scale={[1, 1, 0.3]} color={blade} glow={glow} glowIntensity={0.6} />
      {glow && <pointLight position={[0, 0.5, 0.1]} intensity={0.2} distance={1.2} color={glow} />}
    </group>
  )
}

export function Bacchetta() {
  return (
    <group rotation={[0.8, 0, 0]}>
      <Cyl rt={0.012} rb={0.018} h={0.34} position={[0, 0.08, 0]} color="#3b2a1e" />
      <Star outer={0.055} inner={0.024} position={[0, 0.27, -0.007]} color="#ffe066" glow="#ffcc00" glowIntensity={1} />
      <Sparkles count={14} scale={0.3} size={2.5} position={[0, 0.3, 0]} color="#ffe680" speed={0.6} />
    </group>
  )
}

// ---------------------------------------------------------------- mano sinistra

export function Libro({ cover = "#c8343e", big = false, glow }) {
  const s = big ? 1.2 : 1
  return (
    <group position={[0.02, -0.02, 0.06]} rotation={[0.1, -0.4, 0.1]} scale={s}>
      <Box args={[0.06, 0.28, 0.21]} radius={0.015} color={cover} />
      <Box args={[0.05, 0.26, 0.19]} radius={0.005} position={[0, 0, 0.012]} color="#fbf3dc" outline={false} />
      {glow && (
        <>
          <Ball r={0.03} position={[0.035, 0, 0]} color={glow} glow={glow} glowIntensity={1} />
          {[-1, 1].map((a) =>
            [-1, 1].map((b) => <Box key={`${a}${b}`} args={[0.064, 0.04, 0.04]} radius={0.01} position={[0, a * 0.125, b * 0.09]} color="#f2c230" />)
          )}
        </>
      )}
    </group>
  )
}

export function Bussola() {
  const needle = useRef()
  useFrame(({ clock }) => {
    if (needle.current) needle.current.rotation.z = Math.sin(clock.elapsedTime * 1.3) * 0.5
  })

  return (
    <group position={[0, 0, 0.07]} rotation={[0.2, 0, 0]}>
      <Cyl rt={0.075} h={0.03} rotation={[PI / 2, 0, 0]} color="#d9a520" />
      <M position={[0, 0, 0.0155]} color="#fffaf0" outline={false}>
        <circleGeometry args={[0.062, 24]} />
      </M>
      <group ref={needle} position={[0, 0, 0.02]}>
        <Box args={[0.016, 0.05, 0.006]} radius={0.003} position={[0, 0.025, 0]} color="#e23b3b" outline={false} />
        <Box args={[0.016, 0.05, 0.006]} radius={0.003} position={[0, -0.025, 0]} color="#3b4a6b" outline={false} />
      </group>
    </group>
  )
}

export function Lanterna() {
  const flame = useRef()
  useFrame(({ clock }) => {
    if (flame.current) flame.current.intensity = 0.3 + Math.sin(clock.elapsedTime * 9) * 0.06
  })

  return (
    <group position={[0, -0.06, 0.03]}>
      <Ring r={0.04} tube={0.008} arc={PI} position={[0, -0.01, 0]} color="#3a3a46" />
      <Cyl rt={0.03} rb={0.07} h={0.05} position={[0, -0.04, 0]} color="#3a3a46" />
      <Cyl rt={0.055} h={0.13} position={[0, -0.13, 0]} color="#ffd36b" glow="#ffae2b" glowIntensity={1.3} opacity={0.85} />
      <Cyl rt={0.07} h={0.025} position={[0, -0.205, 0]} color="#3a3a46" />
      <pointLight ref={flame} position={[0, -0.13, 0]} distance={1.8} color="#ffb347" />
    </group>
  )
}

export function Pergamena() {
  return (
    <group position={[0, 0, 0.05]} rotation={[0.3, 0, 0.15]}>
      <Cyl rt={0.04} h={0.24} color="#f1e2b8" />
      <Cyl rt={0.05} h={0.02} position={[0, 0.13, 0]} color="#8a5a2b" />
      <Cyl rt={0.05} h={0.02} position={[0, -0.13, 0]} color="#8a5a2b" />
      <Ring r={0.042} tube={0.008} rotation={[PI / 2, 0, 0]} color="#c0392b" />
    </group>
  )
}

export function Scudo({ emblem }) {
  const bg = emblem?.bg || "#2f5fd0"
  const fg = emblem?.fg || "#ffd54a"
  const symbol = emblem?.symbol || "star"

  return (
    <group position={[0.06, 0.02, 0.05]} rotation={[0, 0.9, 0]}>
      <Cyl rt={0.24} h={0.04} seg={28} rotation={[PI / 2, 0, 0]} color="#b0b8c6" />
      <Cyl rt={0.2} h={0.045} seg={28} rotation={[PI / 2, 0, 0]} position={[0, 0, 0.003]} color={bg} />
      <group position={[0, 0, 0.026]}>
        {symbol === "star" && <Star outer={0.12} inner={0.05} position={[0, 0, -0.01]} color={fg} />}
        {symbol === "cross" && (
          <>
            <Box args={[0.06, 0.28, 0.02]} radius={0.01} color={fg} />
            <Box args={[0.28, 0.06, 0.02]} radius={0.01} color={fg} />
          </>
        )}
        {symbol === "circle" && <Ring r={0.1} tube={0.025} color={fg} />}
        {symbol === "book" && (
          <>
            <Box args={[0.11, 0.15, 0.02]} radius={0.01} position={[-0.058, 0, 0]} rotation={[0, 0.2, 0]} color={fg} />
            <Box args={[0.11, 0.15, 0.02]} radius={0.01} position={[0.058, 0, 0]} rotation={[0, -0.2, 0]} color={fg} />
          </>
        )}
      </group>
    </group>
  )
}

// ---------------------------------------------------------------- schiena

export function Zainetto() {
  return (
    <group>
      <Box args={[0.3, 0.32, 0.14]} radius={0.06} position={[0, 0.77, -0.19]} color="#f08a24" />
      <Box args={[0.2, 0.12, 0.05]} radius={0.03} position={[0, 0.7, -0.27]} color="#d9701a" />
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.04, 0.3, 0.02]} radius={0.01} position={[s * 0.11, 0.8, 0.135]} color="#d9701a" />
      ))}
    </group>
  )
}

export function Mantello({ color, inner, height, collar }) {
  const cape = useRef()
  useFrame(({ clock }) => {
    if (cape.current) cape.current.rotation.x = 0.06 + Math.sin(clock.elapsedTime * 1.6) * 0.03
  })

  return (
    <group>
      <group ref={cape} position={[0, 0.95, -0.02]}>
        <Cyl rt={0.2} rb={0.2 + height * 0.25} h={height} open start={PI / 2 + 0.15} length={PI - 0.3} seg={18} position={[0, -height / 2, 0]} color={color} side={THREE.DoubleSide} />
        <Cyl rt={0.195} rb={0.195 + height * 0.25} h={height - 0.01} open start={PI / 2 + 0.17} length={PI - 0.34} seg={18} position={[0, -height / 2, 0]} color={inner} side={THREE.BackSide} outline={false} />
      </group>
      {collar && <Ring r={0.15} tube={0.03} rotation={[PI / 2, 0, 0]} position={[0, 0.95, 0]} color={collar} />}
    </group>
  )
}

export function Ali() {
  const left = useRef()
  const right = useRef()
  useFrame(({ clock }) => {
    const a = Math.sin(clock.elapsedTime * 2) * 0.15
    if (left.current) left.current.rotation.y = 0.5 + a
    if (right.current) right.current.rotation.y = -0.5 - a
  })

  const wing = (
    <group>
      <Ball r={0.3} scale={[1, 0.55, 0.12]} position={[0.28, 0.08, 0]} rotation={[0, 0, 0.35]} color="#fffaf0" />
      <Ball r={0.22} scale={[1, 0.45, 0.13]} position={[0.3, -0.08, 0]} rotation={[0, 0, 0.1]} color="#fff1c9" />
      <Ball r={0.16} scale={[1, 0.4, 0.14]} position={[0.26, -0.2, 0]} rotation={[0, 0, -0.2]} color="#ffe39a" />
    </group>
  )

  return (
    <group position={[0, 0.85, -0.16]}>
      <group ref={left} position={[0.05, 0, 0]}>
        {wing}
      </group>
      <group ref={right} position={[-0.05, 0, 0]} scale={[-1, 1, 1]}>
        {wing}
      </group>
    </group>
  )
}

// ---------------------------------------------------------------- compagno, aure

export function Gufo() {
  const owl = useRef()
  useFrame(({ clock }) => {
    if (!owl.current) return
    const t = clock.elapsedTime
    owl.current.rotation.y = Math.sin(t * 0.8) * 0.5
    owl.current.position.y = 1.07 + Math.abs(Math.sin(t * 2.5)) * 0.01
  })

  return (
    <group ref={owl} position={[0.24, 1.07, -0.02]}>
      <Ball r={0.1} scale={[1, 1.15, 0.95]} color="#8a5a34" />
      <Ball r={0.07} scale={[1, 1.1, 0.5]} position={[0, -0.02, 0.06]} color="#e9cfa4" outline={false} />
      {[-1, 1].map((s) => (
        <group key={s}>
          <Ball r={0.035} position={[s * 0.04, 0.05, 0.08]} color="#ffffff" />
          <Ball r={0.018} position={[s * 0.04, 0.05, 0.105]} color="#1d1b2e" outline={false} />
          <Cone r={0.025} h={0.06} position={[s * 0.06, 0.12, 0]} rotation={[0, 0, s * -0.35]} color="#6e4426" />
          <Ball r={0.05} scale={[0.4, 1, 0.8]} position={[s * 0.095, -0.02, 0]} color="#6e4426" />
        </group>
      ))}
      <Cone r={0.016} h={0.035} position={[0, 0.015, 0.1]} rotation={[PI, 0, 0]} color="#f0a020" />
    </group>
  )
}

export function AuraScintille() {
  return <Sparkles count={50} scale={[1.1, 1.7, 1.1]} size={3.5} speed={0.4} position={[0, 0.85, 0]} color="#ffe680" />
}

export function AuraFiamme() {
  const ring = useRef()
  useFrame(({ clock }) => {
    if (!ring.current) return
    const t = clock.elapsedTime
    ring.current.children.forEach((flame, i) => {
      flame.scale.y = 0.8 + Math.sin(t * 6 + i * 1.7) * 0.3
    })
  })

  return (
    <group>
      <group ref={ring}>
        {Array.from({ length: 10 }).map((_, i) => {
          const a = (i / 10) * PI * 2
          return (
            <M key={i} position={[Math.sin(a) * 0.42, 0.12, Math.cos(a) * 0.42]} color={i % 2 ? "#ff7a1a" : "#ffc233"} glow={i % 2 ? "#ff5500" : "#ffaa00"} glowIntensity={1.2} opacity={0.8} outline={false}>
              <coneGeometry args={[0.06, 0.24, 8]} />
            </M>
          )
        })}
      </group>
      <Sparkles count={30} scale={[1, 1.5, 1]} size={4} speed={1.2} position={[0, 0.75, 0]} color="#ff9a3c" />
    </group>
  )
}

// ---------------------------------------------------------------- negozio: vestiti

export function Righe({ body }) {
  const w = (body?.torsoW ?? 0.4) + 0.008
  return [0.64, 0.72, 0.8, 0.88].map((y) => (
    <Box key={y} args={[w, 0.035, 0.268]} radius={0.015} position={[0, y, 0]} color="#2a3f7a" outline={false} />
  ))
}

export function Felpa() {
  return (
    <group>
      <Ring r={0.13} tube={0.05} rotation={[PI / 2 - 0.2, 0, 0]} position={[0, 0.97, -0.03]} color="#767b8f" />
      <Ball r={0.13} scale={[1.3, 0.9, 0.55]} position={[0, 0.98, -0.17]} color="#767b8f" />
      <Box args={[0.22, 0.09, 0.02]} radius={0.01} position={[0, 0.67, 0.132]} color="#767b8f" />
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.012, 0.09, 0.012]} radius={0.005} position={[s * 0.04, 0.88, 0.14]} color="#ffffff" outline={false} />
      ))}
    </group>
  )
}

export function StrisceTuta() {
  return [-1, 1].map((s) => <Box key={s} args={[0.012, 0.36, 0.03]} radius={0.005} position={[s * 0.176, 0.32, 0]} color="#ffffff" outline={false} />)
}

// ---------------------------------------------------------------- negozio: testa e viso

export function Fiocco({ color = "#ff5fa2" }) {
  return (
    <group position={[0.16, 0.25, 0.08]} rotation={[0, 0, -0.5]}>
      {[-1, 1].map((s) => (
        <Ball key={s} r={0.06} scale={[1.2, 0.8, 0.5]} position={[s * 0.065, 0, 0]} rotation={[0, 0, s * 0.3]} color={color} />
      ))}
      <Ball r={0.03} color={color} />
    </group>
  )
}

export function Berretto() {
  return (
    <group position={[0, 0.03, 0]} rotation={[-0.15, 0, 0]}>
      <Cap r={0.325} theta={PI * 0.47} color="#2f9b5a" />
      <Ring r={0.315} tube={0.035} rotation={[PI / 2, 0, 0]} position={[0, 0.03, 0]} color="#f4f4f6" />
      <Ball r={0.07} position={[0, 0.34, 0]} color="#f4f4f6" />
    </group>
  )
}

export function Cuffie() {
  return (
    <group position={[0, 0.02, 0]}>
      <Ring r={0.33} tube={0.025} arc={PI} color="#1d1b2e" />
      {[-1, 1].map((s) => (
        <group key={s} position={[s * 0.32, -0.02, 0]}>
          <Cyl rt={0.08} h={0.07} rotation={[0, 0, PI / 2]} color="#e23b3b" />
          <Cyl rt={0.06} h={0.075} rotation={[0, 0, PI / 2]} color="#1d1b2e" outline={false} />
        </group>
      ))}
    </group>
  )
}

export function CappelloPirata() {
  return (
    <group position={[0, 0.06, 0]} rotation={[-0.1, 0, 0]}>
      <Cap r={0.32} theta={PI * 0.42} color="#1d1b2e" />
      {[0, 1, 2].map((i) => (
        <Box
          key={i}
          args={[0.5, 0.16, 0.04]}
          radius={0.02}
          position={[Math.sin((i * 2 * PI) / 3) * 0.26, 0.2, Math.cos((i * 2 * PI) / 3) * 0.26]}
          rotation={[-0.35, (i * 2 * PI) / 3, 0]}
          color="#26243a"
        />
      ))}
      <Ball r={0.05} position={[0, 0.25, 0.3]} color="#ffffff" />
      <Ring r={0.33} tube={0.012} rotation={[PI / 2, 0, 0]} position={[0, 0.13, 0]} color="#f2c230" />
    </group>
  )
}

export function OcchialiSole() {
  return (
    <group position={[0, 0.01, 0.292]}>
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.11, 0.075, 0.02]} radius={0.03} position={[s * 0.1, 0, 0]} color="#1d1b2e" glow="#2a3f7a" glowIntensity={0.25} />
      ))}
      <Box args={[0.06, 0.016, 0.016]} radius={0.006} position={[0, 0.02, 0]} color="#1d1b2e" />
    </group>
  )
}

export function OcchialiCuore() {
  return (
    <group position={[0, 0.0, 0.29]}>
      {[-1, 1].map((s) => (
        <Heart key={s} size={0.15} position={[s * 0.1, 0, 0]} color="#ff4f9a" glow="#ff4f9a" glowIntensity={0.3} />
      ))}
      <Box args={[0.06, 0.014, 0.014]} radius={0.006} position={[0, 0.02, 0.02]} color="#c2185b" />
    </group>
  )
}

// ---------------------------------------------------------------- negozio: in mano

export function Gelato() {
  return (
    <group position={[0, 0.05, 0.05]}>
      <Cone r={0.05} h={0.16} position={[0, 0.0, 0]} rotation={[PI, 0, 0]} color="#d9a05b" />
      <Ball r={0.055} position={[0, 0.1, 0]} color="#ff9ec7" />
      <Ball r={0.048} position={[0, 0.17, 0]} color="#fff3c2" />
      <Ball r={0.012} position={[0.02, 0.215, 0.02]} color="#e23b3b" />
    </group>
  )
}

export function Palloncino() {
  const ref = useRef()
  useFrame(({ clock }) => {
    // Inclinato verso l'esterno (mano destra = lato -x), altrimenti finisce dentro la testa.
    if (ref.current) ref.current.rotation.z = 0.5 + Math.sin(clock.elapsedTime * 1.2) * 0.1
  })

  return (
    <group ref={ref} rotation={[0.35, 0, 0.5]}>
      <Box args={[0.006, 0.7, 0.006]} radius={0.002} position={[0, 0.35, 0]} color="#ffffff" outline={false} />
      <Ball r={0.16} scale={[1, 1.15, 1]} position={[0, 0.85, 0]} color="#e23b3b" />
      <Cone r={0.025} h={0.04} position={[0, 0.68, 0]} rotation={[PI, 0, 0]} color="#c42828" />
    </group>
  )
}

export function Microfono() {
  return (
    <group rotation={[0.9, 0, 0]}>
      <Cyl rt={0.03} rb={0.022} h={0.2} position={[0, 0.05, 0]} color="#1d1b2e" />
      <Ball r={0.055} position={[0, 0.19, 0]} color="#b0b8c6" />
    </group>
  )
}

export function Trofeo() {
  return (
    <group position={[0, -0.02, 0.06]}>
      <Box args={[0.12, 0.04, 0.12]} radius={0.01} position={[0, -0.05, 0]} color="#6b4226" />
      <Cyl rt={0.02} h={0.08} position={[0, 0.01, 0]} color="#f2c230" />
      <Cyl rt={0.1} rb={0.05} h={0.14} position={[0, 0.12, 0]} color="#f2c230" glow="#ffcc33" glowIntensity={0.2} />
      {[-1, 1].map((s) => (
        <Ring key={s} r={0.04} tube={0.01} position={[s * 0.1, 0.13, 0]} rotation={[0, PI / 2, 0]} color="#f2c230" />
      ))}
      <Star outer={0.035} inner={0.015} position={[0, 0.12, 0.08]} color="#ffffff" outline={false} />
    </group>
  )
}

// ---------------------------------------------------------------- negozio: compagni e aure

export function Pulcino() {
  const ref = useRef()
  useFrame(({ clock }) => {
    if (ref.current) ref.current.position.y = 1.03 + Math.abs(Math.sin(clock.elapsedTime * 3)) * 0.02
  })

  return (
    <group ref={ref} position={[0.24, 1.03, 0]}>
      <Ball r={0.075} color="#ffd93b" />
      <Ball r={0.05} position={[0, 0.08, 0.02]} color="#ffd93b" />
      {[-1, 1].map((s) => (
        <Ball key={s} r={0.01} position={[s * 0.02, 0.095, 0.065]} color="#1d1b2e" outline={false} />
      ))}
      <Cone r={0.014} h={0.03} position={[0, 0.08, 0.075]} rotation={[PI / 2, 0, 0]} color="#f08a24" />
      <Ball r={0.015} position={[0, 0.135, 0]} color="#ffd93b" />
    </group>
  )
}

export function Gatto() {
  const tail = useRef()
  useFrame(({ clock }) => {
    if (tail.current) tail.current.rotation.z = 0.4 + Math.sin(clock.elapsedTime * 2) * 0.3
  })

  return (
    <group position={[0.48, 0, 0.15]} rotation={[0, -0.5, 0]}>
      <Ball r={0.11} scale={[0.9, 1.1, 1.2]} position={[0, 0.11, 0]} color="#f0a050" />
      <Ball r={0.09} position={[0, 0.27, 0.06]} color="#f0a050" />
      {[-1, 1].map((s) => (
        <group key={s}>
          <Cone r={0.03} h={0.06} position={[s * 0.05, 0.36, 0.05]} rotation={[0, 0, s * -0.3]} color="#f0a050" />
          <Ball r={0.016} scale={[1, 1.3, 0.5]} position={[s * 0.035, 0.285, 0.14]} color="#1d1b2e" outline={false} />
          <Ball r={0.035} scale={[1, 0.8, 1]} position={[s * 0.05, 0.03, 0.09]} color="#ffffff" />
        </group>
      ))}
      <Ball r={0.014} position={[0, 0.26, 0.15]} color="#ff8fa3" outline={false} />
      <group ref={tail} position={[0, 0.08, -0.12]}>
        <Cyl rt={0.02} h={0.2} position={[0, 0.1, 0]} color="#f0a050" />
      </group>
    </group>
  )
}

export function AuraCuori() {
  const ring = useRef()
  useFrame(({ clock }) => {
    if (ring.current) ring.current.rotation.y = clock.elapsedTime * 0.6
  })

  return (
    <group ref={ring}>
      {Array.from({ length: 6 }).map((_, i) => {
        const a = (i / 6) * PI * 2
        return (
          <Heart
            key={i}
            size={0.12}
            position={[Math.sin(a) * 0.5, 0.4 + (i % 3) * 0.35, Math.cos(a) * 0.5]}
            rotation={[0, a, 0]}
            color="#ff5fa2"
            glow="#ff5fa2"
            glowIntensity={0.5}
          />
        )
      })}
    </group>
  )
}

