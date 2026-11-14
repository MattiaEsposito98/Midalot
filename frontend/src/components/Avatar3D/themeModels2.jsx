import { useRef } from "react"
import * as THREE from "three"
import { useFrame } from "@react-three/fiber"
import { Sparkles } from "@react-three/drei"
import { Ball, Box, Cap, Cone, Cyl, M, Ring, Star } from "./parts"

/*
 * Modelli 3D dei temi Cavalieri, Pirati, Spazio e Ninja.
 * Coordinate come in itemModels.jsx (testa: centro della testa; mani: centro
 * della mano; resto: corpo con i piedi a y = 0).
 */

const PI = Math.PI

// ================================================================ Cavalieri e draghi

export function Pennacchio() {
  return (
    <group>
      {[0, 1, 2, 3, 4].map((i) => (
        <Ball key={i} r={0.07 - i * 0.005} position={[0, 0.4 + Math.cos(i * 0.5) * 0.04, 0.12 - i * 0.08]} color="#2f6fe0" />
      ))}
      <Ring r={0.335} tube={0.02} rotation={[PI / 2, 0, 0]} position={[0, 0.025, 0]} color="#ffd54a" outline={false} />
    </group>
  )
}

export function ScudoStemma() {
  return (
    <group position={[0.06, 0.02, 0.05]} rotation={[0, 0.9, 0]}>
      <Box args={[0.36, 0.26, 0.04]} radius={0.03} position={[0, 0.06, 0]} color="#ffd54a" />
      <Box args={[0.25, 0.25, 0.04]} radius={0.03} position={[0, -0.07, 0]} rotation={[0, 0, PI / 4]} color="#ffd54a" />
      <Box args={[0.31, 0.22, 0.045]} radius={0.025} position={[0, 0.06, 0.004]} color="#2f4fa0" />
      <Box args={[0.21, 0.21, 0.045]} radius={0.025} position={[0, -0.065, 0.004]} rotation={[0, 0, PI / 4]} color="#2f4fa0" />
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.04, 0.2, 0.02]} radius={0.008} position={[s * 0.06, -0.02, 0.03]} rotation={[0, 0, s * 0.8]} color="#ffffff" outline={false} />
      ))}
      <Star outer={0.05} inner={0.022} position={[0, 0.1, 0.022]} color="#ffd54a" outline={false} />
    </group>
  )
}

export function Draghetto() {
  const drake = useRef()
  const wings = useRef()
  useFrame(({ clock }) => {
    const t = clock.elapsedTime
    if (drake.current) drake.current.position.y = 1.15 + Math.sin(t * 1.8) * 0.05
    if (wings.current) wings.current.children.forEach((w, i) => (w.rotation.y = (i ? -1 : 1) * (0.4 + Math.sin(t * 7) * 0.4)))
  })

  return (
    <group ref={drake} position={[0.5, 1.15, -0.05]} rotation={[0, -0.5, 0]}>
      <Ball r={0.09} scale={[1, 0.85, 1.25]} color="#2fae5a" />
      <Ball r={0.06} scale={[1, 0.8, 1]} position={[0, -0.02, 0.05]} color="#bfe8a0" outline={false} />
      <group position={[0, 0.08, 0.1]}>
        <Ball r={0.07} color="#2fae5a" />
        <Ball r={0.04} scale={[1, 0.7, 1]} position={[0, -0.02, 0.06]} color="#3fc46c" />
        {[-1, 1].map((s) => (
          <group key={s}>
            <Ball r={0.02} position={[s * 0.035, 0.02, 0.055]} color="#ffffff" />
            <Ball r={0.011} position={[s * 0.035, 0.02, 0.071]} color="#1d1b2e" outline={false} />
            <Cone r={0.015} h={0.06} position={[s * 0.03, 0.08, -0.02]} rotation={[-0.4, 0, s * -0.3]} color="#ffd54a" />
          </group>
        ))}
      </group>
      <group ref={wings}>
        {[-1, 1].map((s) => (
          <group key={s} position={[s * 0.06, 0.05, -0.02]}>
            <Box args={[0.16, 0.01, 0.1]} radius={0.004} position={[s * 0.08, 0.02, 0]} rotation={[0, 0, s * 0.3]} color="#ff8a3d" />
          </group>
        ))}
      </group>
      <Cone r={0.04} h={0.16} position={[0, -0.02, -0.15]} rotation={[-PI / 2 - 0.3, 0, 0]} color="#2fae5a" />
    </group>
  )
}

export function AuraValore() {
  return (
    <group>
      <M position={[0, 0.01, 0]} rotation={[-PI / 2, 0, 0]} color="#ffd54a" glow="#ffc107" glowIntensity={0.9} opacity={0.7} outline={false}>
        <ringGeometry args={[0.42, 0.5, 40]} />
      </M>
      <Sparkles count={45} scale={[1, 1.8, 1]} size={3.5} speed={0.6} position={[0, 0.9, 0]} color="#ffd54a" />
    </group>
  )
}

export function SfondoTorneo() {
  return (
    <group>
      <group position={[0, 0.5, -3]}>
        <Box args={[6, 1.2, 0.4]} radius={0.02} color="#c9b48a" outline={false} />
        {Array.from({ length: 12 }, (_, i) => (
          <Box key={i} args={[0.25, 0.25, 0.4]} radius={0.02} position={[-2.75 + i * 0.5, 0.72, 0]} color="#c9b48a" outline={false} />
        ))}
      </group>
      {[
        [-1.6, "#2f4fa0"],
        [1.6, "#d62828"],
      ].map(([x, c]) => (
        <group key={x} position={[x, 0, -2.4]}>
          <Cyl rt={0.03} h={2.4} position={[0, 1.2, 0]} color="#8a5a34" outline={false} />
          <Box args={[0.5, 0.32, 0.02]} radius={0.01} position={[0.26, 2.2, 0]} color={c} outline={false} />
          <Ball r={0.05} position={[0, 2.43, 0]} color="#ffd54a" outline={false} />
        </group>
      ))}
      <Cyl rt={0.6} rb={0.64} h={0.12} position={[0, -0.06, 0]} color="#7fae5a" seg={40} />
    </group>
  )
}

// ================================================================ Pirati

export function Bandana() {
  return (
    <group position={[0, 0.04, 0]} rotation={[-0.15, 0, 0]}>
      <Cap r={0.315} theta={PI * 0.42} color="#d62828" />
      {[
        [0.08, 0.2],
        [-0.1, 0.24],
        [0.02, 0.12],
      ].map(([x, y]) => (
        <Ball key={x} r={0.02} position={[x, y, 0.22]} color="#ffffff" outline={false} />
      ))}
      <Ball r={0.05} position={[0.05, 0.1, -0.3]} color="#b51f1f" />
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.05, 0.16, 0.02]} radius={0.01} position={[0.05 + s * 0.04, 0.0, -0.31]} rotation={[0.3, 0, s * 0.3]} color="#d62828" />
      ))}
    </group>
  )
}

export function BendaOcchio() {
  return (
    <group position={[0, 0.02, 0]}>
      <Cyl rt={0.06} h={0.02} position={[-0.1, 0, 0.29]} rotation={[PI / 2, 0, 0]} color="#1a1a22" />
      <group rotation={[0, 0, 0.35]}>
        <Cyl rt={0.304} h={0.025} open start={-PI * 0.05} length={PI * 1.6} seg={24} color="#1a1a22" side={THREE.DoubleSide} />
      </group>
    </group>
  )
}

export function GiaccaPirata() {
  return (
    <group>
      {[-1, 1].map((s) =>
        [0.68, 0.76, 0.84].map((y) => <Ball key={`${s}${y}`} r={0.016} position={[s * 0.06, y, 0.138]} color="#ffd54a" outline={false} />)
      )}
      <Box args={[0.3, 0.05, 0.02]} radius={0.01} position={[0, 0.6, 0.132]} color="#3a2416" outline={false} />
      <Box args={[0.06, 0.05, 0.025]} radius={0.008} position={[0, 0.6, 0.14]} color="#ffd54a" outline={false} />
      <Ball r={0.06} scale={[1.4, 0.7, 0.6]} position={[0, 0.95, 0.12]} color="#f4f4f6" />
    </group>
  )
}

export function Sciabola() {
  return (
    <group rotation={[0.8, 0, 0]}>
      <Cyl rt={0.022} h={0.12} position={[0, -0.02, 0]} color="#3a2416" />
      <Ring r={0.06} tube={0.01} arc={PI} position={[0.02, 0.0, 0]} rotation={[0, 0, -PI / 2]} color="#ffd54a" outline={false} />
      <group position={[-0.32, 0.05, 0]}>
        <Ring r={0.36} tube={0.016} arc={PI * 0.32} scale={[1, 1, 0.5]} color="#e8eef6" />
      </group>
    </group>
  )
}

export function Cannocchiale() {
  return (
    <group position={[0.02, 0, 0.05]} rotation={[0, 0, PI / 2 - 0.3]}>
      <Cyl rt={0.03} h={0.16} position={[0, 0.08, 0]} color="#7a4a2a" />
      <Cyl rt={0.038} h={0.14} position={[0, -0.06, 0]} color="#ffd54a" />
      <Cyl rt={0.045} h={0.03} position={[0, -0.13, 0]} color="#c9951a" />
    </group>
  )
}

export function Pappagallo() {
  const bird = useRef()
  useFrame(({ clock }) => {
    if (bird.current) bird.current.rotation.y = Math.sin(clock.elapsedTime * 1.3) * 0.4
  })

  return (
    <group ref={bird} position={[0.44, 1.08, 0.04]}>
      <Ball r={0.08} scale={[0.9, 1.2, 0.9]} color="#e23b3b" />
      <Ball r={0.065} position={[0, 0.11, 0.02]} color="#e23b3b" />
      {[-1, 1].map((s) => (
        <group key={s}>
          <Ball r={0.018} position={[s * 0.035, 0.13, 0.065]} color="#ffffff" />
          <Ball r={0.01} position={[s * 0.035, 0.13, 0.08]} color="#1d1b2e" outline={false} />
          <Ball r={0.05} scale={[0.4, 1.1, 0.9]} position={[s * 0.07, -0.01, -0.01]} color="#2f7de1" />
        </group>
      ))}
      <Cone r={0.022} h={0.05} position={[0, 0.1, 0.08]} rotation={[PI / 2 + 0.6, 0, 0]} color="#ffd54a" />
      <Box args={[0.05, 0.16, 0.02]} radius={0.01} position={[0, -0.14, -0.05]} rotation={[0.4, 0, 0]} color="#2fae5a" />
    </group>
  )
}

export function AuraDobloni() {
  const ring = useRef()
  useFrame(({ clock }) => {
    if (!ring.current) return
    ring.current.rotation.y = clock.elapsedTime * 0.8
    ring.current.children.forEach((c, i) => (c.rotation.y = clock.elapsedTime * 3 + i))
  })

  return (
    <group ref={ring} position={[0, 0.8, 0]}>
      {Array.from({ length: 8 }, (_, i) => {
        const a = (i / 8) * PI * 2
        return (
          <group key={i} position={[Math.sin(a) * 0.55, Math.sin(i * 1.3) * 0.3, Math.cos(a) * 0.55]}>
            <Cyl rt={0.045} h={0.012} rotation={[PI / 2, 0, 0]} color="#ffd54a" glow="#ffc107" glowIntensity={0.5} />
          </group>
        )
      })}
    </group>
  )
}

export function SfondoNave() {
  return (
    <group>
      <M position={[0, -0.3, -4]} rotation={[-PI / 2, 0, 0]} color="#2f8fd0" outline={false}>
        <planeGeometry args={[14, 6]} />
      </M>
      <group position={[1.3, 0, -1.6]}>
        <Cyl rt={0.05} h={3.2} position={[0, 1.6, 0]} color="#7a4a2a" outline={false} />
        <Box args={[1.1, 1.2, 0.03]} radius={0.02} position={[0, 2.0, 0.06]} color="#f4ead2" outline={false} />
        <Box args={[0.4, 0.24, 0.01]} radius={0.005} position={[0.22, 3.1, 0]} color="#1a1a22" outline={false} />
      </group>
      <Box args={[2.4, 0.08, 1.4]} radius={0.02} position={[0, -0.04, -0.2]} color="#a0683a" />
      {[-0.5, -0.2, 0.1, 0.4].map((z) => (
        <Box key={z} args={[2.4, 0.005, 0.01]} radius={0.002} position={[0, 0.002, z]} color="#7a4a2a" outline={false} />
      ))}
    </group>
  )
}

// ================================================================ Spazio

export function CascoSpaziale() {
  return (
    <group>
      <Ball r={0.41} position={[0, 0.02, 0]} color="#bfe8ff" opacity={0.28} outline={false} />
      <Ring r={0.3} tube={0.05} rotation={[PI / 2, 0, 0]} position={[0, -0.33, 0]} color="#eef1f6" />
      <Cyl rt={0.008} h={0.16} position={[0.22, 0.42, 0]} rotation={[0, 0, -0.4]} color="#8a8fa3" />
      <Ball r={0.025} position={[0.255, 0.49, 0]} color="#ff4f4f" glow="#ff4f4f" glowIntensity={1} />
    </group>
  )
}

export function TutaSpaziale() {
  return (
    <group position={[0, 0.78, 0.136]}>
      <Box args={[0.16, 0.12, 0.02]} radius={0.015} color="#8a8fa3" />
      {[
        ["#ff4f4f", -0.045],
        ["#ffd54a", 0],
        ["#49d6ff", 0.045],
      ].map(([c, x]) => (
        <Ball key={c} r={0.015} position={[x, 0.02, 0.012]} color={c} glow={c} glowIntensity={0.6} outline={false} />
      ))}
      <Box args={[0.1, 0.02, 0.01]} radius={0.004} position={[0, -0.03, 0.012]} color="#49d6ff" glow="#49d6ff" glowIntensity={0.5} outline={false} />
    </group>
  )
}

export function Jetpack() {
  const flames = useRef()
  useFrame(({ clock }) => {
    if (flames.current) flames.current.children.forEach((f, i) => (f.scale.y = 0.8 + Math.sin(clock.elapsedTime * 14 + i) * 0.3))
  })

  return (
    <group position={[0, 0.78, -0.22]}>
      {[-1, 1].map((s) => (
        <group key={s} position={[s * 0.09, 0, 0]}>
          <Cyl rt={0.075} h={0.32} color="#d9dde6" />
          <Cone r={0.075} h={0.08} position={[0, 0.2, 0]} color="#ff4f4f" />
          <Cyl rt={0.05} rb={0.06} h={0.05} position={[0, -0.18, 0]} color="#5d6678" />
        </group>
      ))}
      <group ref={flames}>
        {[-1, 1].map((s) => (
          <Cone key={s} r={0.045} h={0.16} position={[s * 0.09, -0.29, 0]} rotation={[PI, 0, 0]} color="#ffb300" glow="#ff7a00" glowIntensity={1.5} outline={false} />
        ))}
      </group>
    </group>
  )
}

export function PistolaRaggi() {
  return (
    <group position={[0, 0.02, 0.04]} rotation={[0.1, 0, 0]}>
      <Box args={[0.05, 0.12, 0.05]} radius={0.02} position={[0, -0.03, 0]} color="#5b2fc9" />
      <Ball r={0.07} scale={[0.8, 0.8, 1.3]} position={[0, 0.05, 0.05]} color="#d9dde6" />
      {[0.1, 0.14].map((z) => (
        <Ring key={z} r={0.05} tube={0.012} position={[0, 0.05, z]} color="#ffd54a" outline={false} />
      ))}
      <Cone r={0.03} h={0.1} position={[0, 0.05, 0.2]} rotation={[PI / 2, 0, 0]} color="#d9dde6" />
      <Ball r={0.02} position={[0, 0.05, 0.26]} color="#49ff9a" glow="#2fe07a" glowIntensity={1.5} outline={false} />
    </group>
  )
}

export function Scanner() {
  return (
    <group position={[0.02, 0.02, 0.06]} rotation={[0.2, -0.4, 0]}>
      <Box args={[0.1, 0.16, 0.035]} radius={0.015} color="#3a3f55" />
      <Box args={[0.075, 0.08, 0.01]} radius={0.008} position={[0, 0.025, 0.02]} color="#49ff9a" glow="#2fe07a" glowIntensity={0.8} outline={false} />
      <Cyl rt={0.006} h={0.08} position={[0.035, 0.12, 0]} color="#8a8fa3" />
    </group>
  )
}

export function Alieno() {
  const alien = useRef()
  useFrame(({ clock }) => {
    if (!alien.current) return
    const t = clock.elapsedTime
    alien.current.position.y = 1.05 + Math.sin(t * 1.6) * 0.06
    alien.current.rotation.z = Math.sin(t * 1.1) * 0.15
  })

  return (
    <group ref={alien} position={[0.5, 1.05, 0]}>
      <Ball r={0.1} scale={[1, 1.1, 1]} color="#6fe07a" />
      <Ball r={0.035} position={[0, 0.02, 0.085]} color="#ffffff" />
      <Ball r={0.02} position={[0, 0.02, 0.112]} color="#1d1b2e" outline={false} />
      <Box args={[0.04, 0.012, 0.01]} radius={0.005} position={[0, -0.045, 0.095]} color="#2a6b30" outline={false} />
      {[-1, 1].map((s) => (
        <group key={s}>
          <Cyl rt={0.006} h={0.09} position={[s * 0.04, 0.13, 0]} rotation={[0, 0, s * -0.3]} color="#6fe07a" />
          <Ball r={0.02} position={[s * 0.058, 0.18, 0]} color="#ff8fc0" glow="#ff4fa3" glowIntensity={0.8} />
        </group>
      ))}
      <Ring r={0.14} tube={0.012} rotation={[PI / 2 - 0.2, 0, 0]} position={[0, -0.06, 0]} color="#c77dff" glow="#b05cff" glowIntensity={0.8} outline={false} />
    </group>
  )
}

export function AuraPianeti() {
  const orbit = useRef()
  useFrame(({ clock }) => {
    if (orbit.current) orbit.current.rotation.y = clock.elapsedTime * 0.5
  })

  const planets = [
    [0.6, 0.3, "#ff8a5c", 0.06],
    [0.55, -0.2, "#49d6ff", 0.05],
    [0.65, 0.6, "#ffd54a", 0.045],
    [0.5, 0.0, "#c77dff", 0.04],
  ]

  return (
    <group position={[0, 0.85, 0]}>
      <group ref={orbit}>
        {planets.map(([r, y, c, s], i) => {
          const a = (i / planets.length) * PI * 2
          return <Ball key={c} r={s} position={[Math.sin(a) * r, y, Math.cos(a) * r]} color={c} glow={c} glowIntensity={0.4} />
        })}
      </group>
      <Sparkles count={30} scale={[1.2, 1.6, 1.2]} size={2.5} speed={0.2} color="#ffffff" />
    </group>
  )
}

export function SfondoLuna() {
  return (
    <group>
      <group position={[-1.6, 2.4, -3.2]}>
        <Ball r={0.45} color="#2f7de1" outline={false} />
        <Ball r={0.2} scale={[1.4, 0.9, 0.5]} position={[0.1, 0.1, 0.3]} color="#3fbf5a" outline={false} />
      </group>
      <Sparkles count={120} scale={[7, 4, 3]} size={2} speed={0.1} position={[0, 1.4, -2.5]} color="#ffffff" />
      <M position={[0, -0.02, -1.5]} rotation={[-PI / 2, 0, 0]} color="#b9bccb" outline={false}>
        <circleGeometry args={[4.5, 48]} />
      </M>
      {[
        [0.9, -0.6, 0.3],
        [-1.2, -1.2, 0.45],
        [1.6, -2.4, 0.5],
      ].map(([x, z, r]) => (
        <Ball key={x} r={r} scale={[1, 0.12, 1]} position={[x, -0.01, z]} color="#9a9db0" outline={false} />
      ))}
      <group position={[1.7, 0, -2.2]}>
        <Ball r={0.4} scale={[1, 0.8, 1]} position={[0, 0.2, 0]} color="#eef1f6" opacity={0.85} />
        <Cyl rt={0.42} h={0.12} position={[0, 0.06, 0]} color="#8a8fa3" outline={false} />
      </group>
    </group>
  )
}

// ================================================================ Ninja

export function FasciaNinja() {
  return (
    <group position={[0, 0.1, 0]}>
      <Ring r={0.305} tube={0.03} rotation={[PI / 2, 0, 0]} color="#d62828" />
      <Box args={[0.09, 0.06, 0.02]} radius={0.01} position={[0, 0.0, 0.31]} color="#c9ccd6" />
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.04, 0.2, 0.015]} radius={0.007} position={[s * 0.03, -0.1, -0.31]} rotation={[0.2, 0, s * 0.35]} color="#d62828" />
      ))}
    </group>
  )
}

export function MascheraNinja() {
  return (
    <group position={[0, -0.14, 0]}>
      <Cyl rt={0.3} rb={0.26} h={0.15} open start={-PI * 0.55} length={PI * 1.1} seg={24} color="#22252e" side={THREE.DoubleSide} />
    </group>
  )
}

export function Kimono() {
  return (
    <group>
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.04, 0.3, 0.012]} radius={0.006} position={[s * 0.05, 0.8, 0.137]} rotation={[0, 0, s * -0.45]} color="#f4f4f6" outline={false} />
      ))}
    </group>
  )
}

export function CinturaNera() {
  return (
    <group>
      <Cyl rt={0.22} h={0.06} position={[0, 0.58, 0]} color="#16181f" />
      <Box args={[0.08, 0.05, 0.03]} radius={0.01} position={[0.06, 0.58, 0.21]} color="#16181f" />
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.035, 0.14, 0.015]} radius={0.006} position={[0.06 + s * 0.025, 0.5, 0.22]} rotation={[0, 0, s * 0.25]} color="#16181f" />
      ))}
    </group>
  )
}

export function Katana() {
  return (
    <group rotation={[0.8, 0, 0]}>
      <Cyl rt={0.022} h={0.16} position={[0, -0.01, 0]} color="#22252e" />
      {[-0.06, -0.02, 0.02, 0.06].map((y) => (
        <Box key={y} args={[0.05, 0.012, 0.05]} radius={0.004} position={[0, y, 0]} rotation={[0, PI / 4, 0]} color="#d62828" outline={false} />
      ))}
      <Cyl rt={0.05} h={0.015} position={[0, 0.08, 0]} color="#ffd54a" />
      <group position={[0, 0.42, 0]} rotation={[0, 0, -0.06]}>
        <Box args={[0.035, 0.66, 0.012]} radius={0.006} color="#e8eef6" />
      </group>
    </group>
  )
}

export function StelleLancio() {
  const stars = useRef()
  useFrame(({ clock }) => {
    if (stars.current) stars.current.children.forEach((s, i) => (s.rotation.z = clock.elapsedTime * (2 + i)))
  })

  return (
    <group ref={stars} position={[0.02, 0.02, 0.06]}>
      {[
        [0, 0, 0],
        [0.05, 0.07, -0.02],
        [-0.04, 0.09, 0.02],
      ].map(([x, y, z]) => (
        <group key={x} position={[x, y, z]}>
          {[0, 1].map((r) => (
            <Box key={r} args={[0.12, 0.025, 0.006]} radius={0.002} rotation={[0, 0, (r * PI) / 2 + PI / 4]} color="#b7bfcc" />
          ))}
          <Cyl rt={0.012} h={0.01} rotation={[PI / 2, 0, 0]} color="#5d6678" outline={false} />
        </group>
      ))}
    </group>
  )
}

export function Panda() {
  const panda = useRef()
  useFrame(({ clock }) => {
    if (panda.current) panda.current.rotation.y = Math.sin(clock.elapsedTime * 0.9) * 0.4
  })

  return (
    <group ref={panda} position={[0.44, 1.08, 0.04]}>
      <Ball r={0.085} scale={[1, 0.95, 0.95]} color="#f4f4f6" />
      {[-1, 1].map((s) => (
        <group key={s}>
          <Ball r={0.03} position={[s * 0.065, 0.07, 0]} color="#1d1b2e" />
          <Ball r={0.024} scale={[1, 1.3, 0.6]} position={[s * 0.032, 0.01, 0.07]} rotation={[0, 0, s * 0.4]} color="#1d1b2e" outline={false} />
          <Ball r={0.009} position={[s * 0.032, 0.015, 0.085]} color="#ffffff" outline={false} />
        </group>
      ))}
      <Ball r={0.012} position={[0, -0.025, 0.085]} color="#1d1b2e" outline={false} />
    </group>
  )
}

export function AuraPetali() {
  return (
    <group>
      <Sparkles count={50} scale={[1.2, 1.8, 1.2]} size={4} speed={0.3} position={[0, 0.9, 0]} color="#ffb3d1" />
      <Sparkles count={20} scale={[1.2, 1.8, 1.2]} size={2.5} speed={0.5} position={[0, 0.9, 0]} color="#ffffff" />
    </group>
  )
}

export function SfondoTempio() {
  return (
    <group>
      <Cone r={2.2} h={2.4} seg={6} position={[-1.6, 1.0, -4.2]} color="#8a9ab8" outline={false} />
      <Cone r={0.6} h={0.5} seg={6} position={[-1.6, 2.0, -4.2]} color="#ffffff" outline={false} />
      <Ball r={0.35} position={[1.8, 2.6, -3.6]} color="#ff7a5c" glow="#ff5a3c" glowIntensity={0.5} outline={false} />
      <group position={[1.4, 0, -2.6]}>
        {[0, 1, 2].map((i) => (
          <group key={i} position={[0, 0.35 + i * 0.6, 0]}>
            <Box args={[0.7 - i * 0.15, 0.4, 0.7 - i * 0.15]} radius={0.02} color="#d62828" outline={false} />
            <Cyl rt={0.3} rb={0.62 - i * 0.12} h={0.16} seg={4} position={[0, 0.27, 0]} rotation={[0, PI / 4, 0]} color="#2b2e38" outline={false} />
          </group>
        ))}
      </group>
      <Sparkles count={30} scale={[6, 3, 2]} size={3} speed={0.3} position={[0, 1.5, -2]} color="#ffb3d1" />
      <Cyl rt={0.6} rb={0.64} h={0.12} position={[0, -0.06, 0]} color="#b89a6a" seg={40} />
    </group>
  )
}
