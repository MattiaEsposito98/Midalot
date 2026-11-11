import { useRef } from "react"
import { useFrame } from "@react-three/fiber"
import { Sparkles } from "@react-three/drei"
import { Ball, Box, Cone, Cyl, M, Ring, Star } from "./parts"

/*
 * Modelli 3D dei temi stagionali (Natale, Halloween, Carnevale).
 * Coordinate come in itemModels.jsx.
 */

const PI = Math.PI

// ================================================================ Natale

export function CappelloNatale() {
  return (
    <group position={[0, 0.1, 0]} rotation={[-0.1, 0, 0]}>
      <Cyl rt={0.33} h={0.09} seg={32} position={[0, 0.06, 0]} color="#f4f4f6" />
      <group position={[0, 0.3, -0.04]} rotation={[-0.5, 0, 0.35]}>
        <Cone r={0.3} h={0.48} seg={28} color="#d62828" />
      </group>
      <Ball r={0.07} position={[0.17, 0.45, -0.22]} color="#f4f4f6" />
    </group>
  )
}

export function MaglioneNatale() {
  return (
    <group>
      {[-0.08, -0.04, 0, 0.04, 0.08].map((x, i) => (
        <Box key={x} args={[0.035, 0.035, 0.012]} radius={0.004} position={[x, 0.72, 0.137]} rotation={[0, 0, PI / 4]} color={i % 2 ? "#2fae5a" : "#f4f4f6"} outline={false} />
      ))}
      <Star outer={0.045} inner={0.02} position={[0, 0.84, 0.136]} color="#f4f4f6" outline={false} />
    </group>
  )
}

export function SciarpaNatale() {
  const stripes = ["#d62828", "#f4f4f6", "#2fae5a", "#f4f4f6", "#d62828"]
  return (
    <group>
      <Ring r={0.15} tube={0.05} rotation={[PI / 2, 0, 0]} position={[0, 0.98, 0]} color="#d62828" />
      {stripes.map((c, i) => (
        <Box key={i} args={[0.08, 0.05, 0.03]} radius={0.01} position={[-0.09, 0.92 - i * 0.05, 0.15]} rotation={[0.1, 0, -0.08]} color={c} />
      ))}
    </group>
  )
}

export function Bastoncino() {
  return (
    <group rotation={[0.6, 0, 0]}>
      {Array.from({ length: 7 }, (_, i) => (
        <Cyl key={i} rt={0.022} h={0.05} position={[0, -0.05 + i * 0.05, 0]} color={i % 2 ? "#f4f4f6" : "#d62828"} outline={false} />
      ))}
      <Ring r={0.06} tube={0.022} arc={PI} position={[-0.06, 0.28, 0]} color="#d62828" />
    </group>
  )
}

export function Regalo() {
  return (
    <group position={[0.02, 0.02, 0.06]} rotation={[0, -0.4, 0]}>
      <Box args={[0.2, 0.18, 0.2]} radius={0.02} color="#2fae5a" />
      <Box args={[0.04, 0.185, 0.205]} radius={0.005} color="#ffd54a" outline={false} />
      <Box args={[0.205, 0.185, 0.04]} radius={0.005} color="#ffd54a" outline={false} />
      {[-1, 1].map((s) => (
        <Ring key={s} r={0.035} tube={0.012} position={[s * 0.035, 0.115, 0]} rotation={[0, 0, s * 0.6]} color="#ffd54a" />
      ))}
    </group>
  )
}

export function Renna() {
  const deer = useRef()
  useFrame(({ clock }) => {
    if (deer.current) deer.current.position.y = 0.0 + Math.abs(Math.sin(clock.elapsedTime * 2.2)) * 0.03
  })

  return (
    <group ref={deer} position={[0.62, 0, 0.1]} rotation={[0, -0.6, 0]}>
      <Ball r={0.14} scale={[0.8, 0.75, 1.2]} position={[0, 0.32, 0]} color="#8a5a34" />
      {[
        [-1, -1],
        [-1, 1],
        [1, -1],
        [1, 1],
      ].map(([x, z]) => (
        <Cyl key={`${x}${z}`} rt={0.025} h={0.22} position={[x * 0.06, 0.12, z * 0.1]} color="#6e4426" />
      ))}
      <group position={[0, 0.5, 0.14]}>
        <Ball r={0.09} color="#8a5a34" />
        <Ball r={0.035} position={[0, -0.02, 0.085]} color="#e23b3b" glow="#ff3b3b" glowIntensity={1} />
        {[-1, 1].map((s) => (
          <group key={s}>
            <Ball r={0.016} position={[s * 0.035, 0.03, 0.075]} color="#1d1b2e" outline={false} />
            <Cyl rt={0.01} h={0.14} position={[s * 0.05, 0.13, -0.02]} rotation={[0, 0, s * -0.4]} color="#c9a36a" />
            <Cyl rt={0.008} h={0.06} position={[s * 0.09, 0.17, -0.02]} rotation={[0, 0, s * 0.6]} color="#c9a36a" />
          </group>
        ))}
      </group>
    </group>
  )
}

export function AuraNeve() {
  return (
    <group>
      <Sparkles count={70} scale={[1.6, 2.2, 1.6]} size={4} speed={0.25} position={[0, 1, 0]} color="#ffffff" />
      <Sparkles count={20} scale={[1.2, 1.8, 1.2]} size={2} speed={0.4} position={[0, 0.9, 0]} color="#bfe8ff" />
    </group>
  )
}

export function SfondoNeve() {
  return (
    <group>
      <M position={[0, -0.02, -1.5]} rotation={[-PI / 2, 0, 0]} color="#f4f8ff" outline={false}>
        <circleGeometry args={[4.5, 48]} />
      </M>
      <group position={[-1.5, 0, -2.2]}>
        {[0, 1, 2].map((i) => (
          <Cone key={i} r={0.55 - i * 0.14} h={0.6} seg={14} position={[0, 0.5 + i * 0.38, 0]} color="#2f8a4a" outline={false} />
        ))}
        <Star outer={0.12} inner={0.05} position={[0, 1.55, 0]} color="#ffd54a" glow="#ffcc00" glowIntensity={1} outline={false} />
        {[
          [0.2, 0.55, 0.25, "#e23b3b"],
          [-0.15, 0.85, 0.2, "#ffd54a"],
          [0.1, 1.1, 0.15, "#2f7de1"],
        ].map(([x, y, z, c]) => (
          <Ball key={y} r={0.04} position={[x, y, z]} color={c} glow={c} glowIntensity={0.6} outline={false} />
        ))}
      </group>
      <group position={[1.6, 0, -2]}>
        <Ball r={0.3} position={[0, 0.25, 0]} color="#ffffff" />
        <Ball r={0.2} position={[0, 0.66, 0]} color="#ffffff" />
        <Cone r={0.03} h={0.12} position={[0, 0.66, 0.22]} rotation={[PI / 2, 0, 0]} color="#f08a24" />
      </group>
      <Sparkles count={80} scale={[7, 4, 3]} size={3} speed={0.3} position={[0, 1.6, -1.5]} color="#ffffff" />
    </group>
  )
}

// ================================================================ Halloween

export function CappelloStrega() {
  return (
    <group position={[0, 0.1, 0]} rotation={[-0.06, 0, -0.08]}>
      <Cyl rt={0.48} h={0.025} seg={36} position={[0, 0.03, 0]} color="#1d1b2e" />
      <Cyl rt={0.31} rb={0.32} h={0.07} seg={32} position={[0, 0.075, 0]} color="#f08a24" />
      <group position={[0, 0.39, 0]} rotation={[0, 0, 0.22]}>
        <Cone r={0.31} h={0.62} seg={32} color="#1d1b2e" />
      </group>
      <Box args={[0.08, 0.08, 0.02]} radius={0.01} position={[0, 0.075, 0.32]} color="#ffd54a" />
    </group>
  )
}

export function Scheletro() {
  return (
    <group>
      {[0.7, 0.77, 0.84].map((y) => (
        <Box key={y} args={[0.2, 0.025, 0.012]} radius={0.01} position={[0, y, 0.137]} color="#f4f4f6" outline={false} />
      ))}
      <Box args={[0.03, 0.26, 0.012]} radius={0.01} position={[0, 0.77, 0.138]} color="#f4f4f6" outline={false} />
    </group>
  )
}

export function OssaGambe() {
  return (
    <group>
      {[-1, 1].map((s) => (
        <Box key={s} args={[0.025, 0.36, 0.012]} radius={0.01} position={[s * 0.09, 0.28, 0.1]} color="#f4f4f6" outline={false} />
      ))}
    </group>
  )
}

export function LeccaLecca() {
  return (
    <group rotation={[0.6, 0, 0]}>
      <Cyl rt={0.012} h={0.3} position={[0, 0.06, 0]} color="#f4f4f6" />
      <Cyl rt={0.1} h={0.03} position={[0, 0.27, 0]} rotation={[PI / 2, 0, 0]} color="#7b3fe4" />
      <Ring r={0.06} tube={0.016} position={[0, 0.27, 0.018]} color="#f08a24" outline={false} />
      <Ring r={0.025} tube={0.012} position={[0, 0.27, 0.02]} color="#6fbf3a" outline={false} />
    </group>
  )
}

export function Zucca() {
  return (
    <group position={[0.02, -0.04, 0.06]}>
      <Ball r={0.13} scale={[1.15, 0.9, 1.1]} color="#f08a24" />
      {[-1, 1].map((s) => (
        <Cone key={s} r={0.025} h={0.04} seg={3} position={[s * 0.045, 0.03, 0.115]} rotation={[PI / 2, 0, 0]} color="#ffd54a" glow="#ffb300" glowIntensity={1.4} outline={false} />
      ))}
      <Box args={[0.1, 0.02, 0.02]} radius={0.006} position={[0, -0.03, 0.12]} color="#ffd54a" glow="#ffb300" glowIntensity={1.4} outline={false} />
      <Cyl rt={0.015} h={0.06} position={[0, 0.13, 0]} color="#3a6b2a" />
      <pointLight position={[0, 0, 0.2]} intensity={0.25} distance={0.8} color="#ffb300" />
    </group>
  )
}

export function Pipistrello() {
  const bat = useRef()
  const wings = useRef()
  useFrame(({ clock }) => {
    const t = clock.elapsedTime
    if (bat.current) {
      bat.current.position.y = 1.2 + Math.sin(t * 2) * 0.06
      bat.current.position.x = 0.5 + Math.sin(t * 0.8) * 0.06
    }
    if (wings.current) wings.current.children.forEach((w, i) => (w.rotation.z = (i ? -1 : 1) * Math.sin(t * 10) * 0.5))
  })

  return (
    <group ref={bat} position={[0.5, 1.2, 0]}>
      <Ball r={0.06} color="#2b2440" />
      {[-1, 1].map((s) => (
        <group key={s}>
          <Cone r={0.018} h={0.05} position={[s * 0.03, 0.06, 0]} color="#2b2440" />
          <Ball r={0.012} position={[s * 0.022, 0.01, 0.052]} color="#ffd54a" glow="#ffd54a" glowIntensity={0.8} outline={false} />
        </group>
      ))}
      <group ref={wings}>
        {[-1, 1].map((s) => (
          <group key={s}>
            <Box args={[0.16, 0.08, 0.008]} radius={0.004} position={[s * 0.11, 0.01, 0]} color="#3d3360" />
          </group>
        ))}
      </group>
    </group>
  )
}

export function AuraSpettrale() {
  const ghosts = useRef()
  useFrame(({ clock }) => {
    if (!ghosts.current) return
    ghosts.current.rotation.y = clock.elapsedTime * 0.5
    ghosts.current.children.forEach((g, i) => (g.position.y = Math.sin(clock.elapsedTime * 1.5 + i * 2) * 0.12))
  })

  return (
    <group>
      <group ref={ghosts} position={[0, 0.9, 0]}>
        {[0, 1, 2].map((i) => {
          const a = (i / 3) * PI * 2
          return (
            <group key={i} position={[Math.sin(a) * 0.6, 0, Math.cos(a) * 0.6]}>
              <Ball r={0.07} scale={[1, 1.3, 1]} color="#ffffff" opacity={0.8} outline={false} />
              {[-1, 1].map((s) => (
                <Ball key={s} r={0.012} position={[s * 0.025, 0.02, 0.06]} color="#1d1b2e" outline={false} />
              ))}
            </group>
          )
        })}
      </group>
      <Sparkles count={30} scale={[1.2, 1.6, 1.2]} size={3} speed={0.3} position={[0, 0.85, 0]} color="#ffb300" />
    </group>
  )
}

export function SfondoHalloween() {
  return (
    <group>
      <Ball r={0.45} position={[1.6, 2.4, -3.2]} color="#ffe9a8" glow="#ffd84a" glowIntensity={0.6} outline={false} />
      <group position={[-1.6, 0, -2.6]}>
        <Box args={[1.0, 1.0, 0.8]} radius={0.02} position={[0, 0.5, 0]} color="#3d3360" outline={false} />
        <Cone r={0.8} h={0.6} seg={4} position={[0, 1.3, 0]} rotation={[0, PI / 4, 0]} color="#2b2440" outline={false} />
        {[-0.25, 0.25].map((x) => (
          <Box key={x} args={[0.18, 0.2, 0.02]} radius={0.02} position={[x, 0.6, 0.41]} color="#ffb300" glow="#ff9a00" glowIntensity={0.7} outline={false} />
        ))}
      </group>
      {[
        [0.9, -0.8],
        [-0.8, -0.5],
      ].map(([x, z]) => (
        <Ball key={x} r={0.16} scale={[1.15, 0.9, 1.1]} position={[x, 0.12, z]} color="#f08a24" />
      ))}
      <Sparkles count={40} scale={[7, 3, 2]} size={2} speed={0.2} position={[0, 2.4, -3]} color="#c77dff" />
      <Cyl rt={0.6} rb={0.64} h={0.12} position={[0, -0.06, 0]} color="#3d3550" seg={40} />
    </group>
  )
}

// ================================================================ Carnevale

const PARTY = ["#e23b3b", "#ffd54a", "#2f7de1", "#2fae5a", "#c77dff", "#ff8fc0"]

export function CappelloGiullare() {
  return (
    <group position={[0, 0.08, 0]}>
      <Cyl rt={0.32} h={0.12} seg={32} position={[0, 0.08, 0]} color="#7b3fe4" />
      {[-1, 0, 1].map((s, i) => (
        <group key={s} position={[s * 0.18, 0.2, s === 0 ? -0.05 : 0]} rotation={[0, 0, s * -0.9]}>
          <Cone r={0.09} h={0.34} seg={16} position={[0, 0.15, 0]} color={["#e23b3b", "#ffd54a", "#2fae5a"][i]} />
          <Ball r={0.035} position={[0, 0.33, 0]} color="#ffd54a" glow="#ffcc00" glowIntensity={0.4} />
        </group>
      ))}
    </group>
  )
}

export function MascheraCarnevale() {
  return (
    <group position={[0, -0.03, 0.27]}>
      <Box args={[0.36, 0.11, 0.04]} radius={0.04} color="#c77dff" />
      {[-1, 1].map((s) => (
        <Ball key={s} r={0.04} scale={[1.3, 0.8, 0.5]} position={[s * 0.1, 0, 0.02]} color="#1d1b2e" outline={false} />
      ))}
      <Box args={[0.36, 0.015, 0.045]} radius={0.006} position={[0, 0.05, 0]} color="#ffd54a" outline={false} />
      {[-0.06, 0, 0.06].map((x, i) => (
        <Ball key={x} r={0.04} scale={[0.35, 1.4, 0.2]} position={[0.16 + x * 0.5, 0.12 + i * 0.02, -0.02]} rotation={[0, 0, -0.4 + i * 0.25]} color={PARTY[i]} />
      ))}
    </group>
  )
}

export function Arlecchino() {
  const cells = []
  for (let r = 0; r < 3; r++) for (let c = -2; c <= 2; c++) cells.push([c, r])
  return (
    <group>
      {cells.map(([c, r], i) => (
        <Box
          key={i}
          args={[0.05, 0.05, 0.01]}
          radius={0.004}
          position={[c * 0.055 + (r % 2) * 0.0275, 0.68 + r * 0.075, 0.137]}
          rotation={[0, 0, PI / 4]}
          color={PARTY[(c + r + 6) % PARTY.length]}
          outline={false}
        />
      ))}
    </group>
  )
}

export function Trombetta() {
  return (
    <group rotation={[0.6, 0, 0]}>
      <Cyl rt={0.016} rb={0.012} h={0.2} position={[0, 0.08, 0]} color="#ffd54a" />
      <Cone r={0.05} h={0.08} position={[0, 0.2, 0]} rotation={[PI, 0, 0]} color="#e23b3b" />
      {[0.03, 0.09].map((y) => (
        <Ring key={y} r={0.018} tube={0.005} rotation={[PI / 2, 0, 0]} position={[0, y, 0]} color="#2f7de1" outline={false} />
      ))}
    </group>
  )
}

export function StelleFilanti() {
  return (
    <group position={[0.02, 0.05, 0.06]}>
      {PARTY.slice(0, 4).map((c, i) => (
        <group key={c} rotation={[0, i * 0.8, 0.3 - i * 0.2]}>
          <Ring r={0.07 + i * 0.012} tube={0.008} arc={PI * 1.4} position={[0, i * 0.03, 0]} color={c} outline={false} />
        </group>
      ))}
    </group>
  )
}

export function Cagnolino() {
  const pup = useRef()
  useFrame(({ clock }) => {
    if (pup.current) pup.current.position.y = Math.abs(Math.sin(clock.elapsedTime * 3)) * 0.04
  })

  return (
    <group ref={pup} position={[0.55, 0, 0.15]} rotation={[0, -0.6, 0]}>
      <Ball r={0.12} scale={[0.85, 0.8, 1.15]} position={[0, 0.2, 0]} color="#e9c08a" />
      {[
        [-1, -1],
        [-1, 1],
        [1, -1],
        [1, 1],
      ].map(([x, z]) => (
        <Cyl key={`${x}${z}`} rt={0.025} h={0.1} position={[x * 0.06, 0.06, z * 0.08]} color="#d9a868" />
      ))}
      <group position={[0, 0.34, 0.1]}>
        <Ball r={0.09} color="#e9c08a" />
        <Ball r={0.04} scale={[1, 0.8, 1]} position={[0, -0.02, 0.08]} color="#f4dcb4" />
        <Ball r={0.014} position={[0, 0.0, 0.12]} color="#1d1b2e" outline={false} />
        {[-1, 1].map((s) => (
          <group key={s}>
            <Ball r={0.015} position={[s * 0.035, 0.03, 0.08]} color="#1d1b2e" outline={false} />
            <Ball r={0.04} scale={[0.5, 1.2, 0.4]} position={[s * 0.08, 0.0, 0]} rotation={[0, 0, s * 0.3]} color="#a8743c" />
          </group>
        ))}
        <Cone r={0.045} h={0.12} position={[0, 0.13, 0]} color="#7b3fe4" />
        <Ball r={0.018} position={[0, 0.2, 0]} color="#ffd54a" />
      </group>
      <Cyl rt={0.012} h={0.1} position={[0, 0.26, -0.14]} rotation={[-0.8, 0, 0]} color="#d9a868" />
    </group>
  )
}

export function AuraCoriandoli() {
  return (
    <group>
      {PARTY.map((c, i) => (
        <Sparkles key={c} count={12} scale={[1.4, 2, 1.4]} size={4} speed={0.5 + i * 0.05} position={[0, 0.95, 0]} color={c} />
      ))}
    </group>
  )
}

export function SfondoCarnevale() {
  return (
    <group>
      {[-1.8, -1.1, 1.2, 1.9].map((x, i) => (
        <group key={x} position={[x, 1.6 + (i % 2) * 0.4, -2.6]}>
          <Ball r={0.22} scale={[1, 1.2, 1]} color={PARTY[i]} />
          <Cyl rt={0.005} h={1.4} position={[0, -0.95, 0]} color="#8a8fa3" outline={false} />
        </group>
      ))}
      {Array.from({ length: 13 }, (_, i) => (
        <Cone key={i} r={0.1} h={0.18} seg={3} position={[-3 + i * 0.5, 2.6 - Math.sin((i / 12) * PI) * 0.4, -2.9]} rotation={[PI, 0, 0]} color={PARTY[i % PARTY.length]} outline={false} />
      ))}
      <Sparkles count={60} scale={[6, 3, 2]} size={4} speed={0.4} position={[0, 1.5, -2]} color="#ffd54a" />
      <Cyl rt={0.6} rb={0.64} h={0.12} position={[0, -0.06, 0]} color="#7b3fe4" seg={40} />
    </group>
  )
}
