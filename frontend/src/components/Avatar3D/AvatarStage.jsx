import { useEffect, useRef } from "react"
import * as THREE from "three"
import { Canvas, useFrame } from "@react-three/fiber"
import { OrbitControls, Sparkles } from "@react-three/drei"
import AvatarModel from "./AvatarModel"
import { Ball, Box, Cone, Cyl, M, Ring } from "./parts"
import { SKY } from "./catalog"

const PI = Math.PI

function Beach() {
  return (
    <group>
      <M position={[0, -0.17, 0]} rotation={[-PI / 2, 0, 0]} color="#f3dca2" outline={false}>
        <circleGeometry args={[4, 48]} />
      </M>
      <M position={[0, -0.16, -2.2]} rotation={[-PI / 2, 0, 0]} color="#3fb6e8" outline={false}>
        <planeGeometry args={[9, 2.4]} />
      </M>
      <Ball r={0.35} position={[1.6, 1.9, -3]} color="#ffd54a" glow="#ffb300" glowIntensity={0.7} outline={false} />
      <group position={[-1.2, -0.17, -1.1]} rotation={[0, 0, 0.12]}>
        <Cyl rt={0.05} rb={0.08} h={1.6} position={[0, 0.8, 0]} color="#a0703c" />
        {[0, 1, 2, 3, 4].map((i) => (
          <Box
            key={i}
            args={[0.7, 0.04, 0.2]}
            radius={0.02}
            position={[Math.cos((i / 5) * PI * 2) * 0.3, 1.58, Math.sin((i / 5) * PI * 2) * 0.3]}
            rotation={[0, -(i / 5) * PI * 2, -0.35]}
            color="#3f9b4b"
          />
        ))}
      </group>
      <Pedestal color="#ffe9b8" rim="#e0c27a" />
    </group>
  )
}

function Space() {
  return (
    <group>
      <Sparkles count={120} scale={[7, 4, 3]} size={2} speed={0.1} position={[0, 1.2, -2]} color="#ffffff" />
      <group position={[1.5, 1.8, -2.5]} rotation={[0.4, 0, 0.3]}>
        <Ball r={0.4} color="#ff8a5c" />
        <Ring r={0.62} tube={0.04} rotation={[PI / 2, 0, 0]} color="#ffd54a" />
      </group>
      <Ball r={0.15} position={[-1.6, 1.6, -2]} color="#9fb6ff" />
      <Cyl rt={0.55} rb={0.6} h={0.12} position={[0, -0.06, 0]} color="#a7a9be" seg={40} />
      <Ball r={0.08} scale={[1, 0.3, 1]} position={[0.3, 0.0, 0.25]} color="#8a8ca3" outline={false} />
      <Ball r={0.06} scale={[1, 0.3, 1]} position={[-0.3, 0.0, -0.2]} color="#8a8ca3" outline={false} />
    </group>
  )
}

// Inquadrature: tutto il corpo, il viso (aspetto) o la testa (cappelli).
const FOCUS = {
  body: { position: new THREE.Vector3(0, 1.15, 3.9), target: new THREE.Vector3(0, 0.88, 0) },
  face: { position: new THREE.Vector3(0, 1.32, 2.15), target: new THREE.Vector3(0, 1.2, 0) },
  head: { position: new THREE.Vector3(0, 1.5, 2.1), target: new THREE.Vector3(0, 1.36, 0) },
}

// Sposta dolcemente la telecamera quando cambia l'inquadratura.
function CameraRig({ focus }) {
  const goal = useRef(null)

  useEffect(() => {
    goal.current = FOCUS[focus] || FOCUS.body
  }, [focus])

  useFrame((state) => {
    const g = goal.current
    if (!g || !state.controls) return
    state.camera.position.lerp(g.position, 0.12)
    state.controls.target.lerp(g.target, 0.12)
    state.controls.update()
    if (state.camera.position.distanceTo(g.position) < 0.003) goal.current = null
  })

  return null
}

function Pedestal({ color = "#7b68ee", rim = "#5b49c9" }) {
  return (
    <group>
      <Cyl rt={0.55} rb={0.58} h={0.1} position={[0, -0.05, 0]} color={color} seg={40} />
      <Cyl rt={0.6} rb={0.62} h={0.05} position={[0, -0.12, 0]} color={rim} seg={40} />
    </group>
  )
}

function Tree({ position, scale = 1 }) {
  return (
    <group position={position} scale={scale}>
      <Cyl rt={0.06} rb={0.08} h={0.4} position={[0, 0.2, 0]} color="#7a5230" />
      <Cone r={0.38} h={0.6} position={[0, 0.6, 0]} color="#3f9b4b" />
      <Cone r={0.3} h={0.5} position={[0, 0.9, 0]} color="#4cb85a" />
    </group>
  )
}

function Forest() {
  return (
    <group>
      <M position={[0, -0.17, 0]} rotation={[-PI / 2, 0, 0]} color="#7cc96b" outline={false}>
        <circleGeometry args={[4, 48]} />
      </M>
      <Tree position={[-1.3, -0.17, -1.4]} scale={1.3} />
      <Tree position={[1.4, -0.17, -1.6]} scale={1.5} />
      <Tree position={[-0.4, -0.17, -2.3]} scale={1.1} />
      <Tree position={[0.7, -0.17, -2.6]} scale={1} />
      <Ball r={0.18} scale={[1.4, 0.7, 1]} position={[-0.9, -0.12, -0.6]} color="#5aa04a" />
      <Ball r={0.14} scale={[1.4, 0.7, 1]} position={[1, -0.12, -0.5]} color="#5aa04a" />
      <Pedestal color="#8fd17e" rim="#5fae4f" />
    </group>
  )
}

const BOOK_COLORS = ["#c8343e", "#2f6fd0", "#2f9b5a", "#f0a020", "#6c4bd8", "#e05fb0"]

function Library() {
  return (
    <group>
      <M position={[0, -0.17, 0]} rotation={[-PI / 2, 0, 0]} color="#8a5a34" outline={false}>
        <circleGeometry args={[4, 48]} />
      </M>
      {[-1, 1].map((side) => (
        <group key={side} position={[side * 0.95, -0.17, -1.2]}>
          <Box args={[1.1, 2.2, 0.35]} radius={0.03} position={[0, 1.1, 0]} color="#6b4226" />
          {[0.35, 0.85, 1.35, 1.85].map((y) => (
            <group key={y} position={[0, y, 0.12]}>
              {Array.from({ length: 8 }).map((_, i) => (
                <Box
                  key={i}
                  args={[0.1, 0.32 + ((i * 7) % 5) * 0.025, 0.2]}
                  radius={0.015}
                  position={[-0.42 + i * 0.12, 0.17, 0]}
                  color={BOOK_COLORS[(i + Math.round(y * 3)) % BOOK_COLORS.length]}
                />
              ))}
            </group>
          ))}
        </group>
      ))}
      <Pedestal color="#a0663c" rim="#6b4226" />
    </group>
  )
}

function Castle() {
  return (
    <group>
      <M position={[0, -0.17, 0]} rotation={[-PI / 2, 0, 0]} color="#b7b2a8" outline={false}>
        <circleGeometry args={[4, 48]} />
      </M>
      <group position={[0, -0.17, -1.8]}>
        <Box args={[3.2, 1.2, 0.3]} radius={0.02} position={[0, 0.6, 0]} color="#c9c2b4" />
        {Array.from({ length: 8 }).map((_, i) => (
          <Box key={i} args={[0.22, 0.2, 0.3]} radius={0.02} position={[-1.5 + i * 0.43, 1.3, 0]} color="#c9c2b4" />
        ))}
        {[-1, 1].map((s) => (
          <group key={s} position={[s * 1.25, 0, 0.1]}>
            <Cyl rt={0.32} h={1.9} position={[0, 0.95, 0]} color="#bdb5a6" />
            <Cone r={0.4} h={0.55} position={[0, 2.17, 0]} color="#c42828" />
            <Box args={[0.3, 0.45, 0.02]} radius={0.01} position={[0, 1.2, 0.33]} color="#2f5fd0" />
          </group>
        ))}
      </group>
      <Pedestal color="#a8a196" rim="#857d70" />
    </group>
  )
}

function Legend() {
  return (
    <group>
      {[0, 1, 2, 3, 4, 5].map((i) => (
        <M key={i} position={[0, 1.2, -1.5]} rotation={[0, 0, (i / 6) * PI * 2]} color="#ffd54a" opacity={0.08} outline={false}>
          <coneGeometry args={[0.5, 4, 3, 1, true]} />
        </M>
      ))}
      <Sparkles count={60} scale={[4, 3, 2]} size={2} speed={0.2} position={[0, 1.2, -1]} color="#fff3b0" />
      <Cyl rt={0.5} rb={0.55} h={0.1} position={[0, -0.05, 0]} color="#f2c230" seg={40} />
      <Cyl rt={0.65} rb={0.7} h={0.1} position={[0, -0.15, 0]} color="#d9a520" seg={40} />
      <Cyl rt={0.8} rb={0.85} h={0.1} position={[0, -0.25, 0]} color="#b8860b" seg={40} />
    </group>
  )
}

function Background({ name }) {
  if (name === "sfondo_foresta") return <Forest />
  if (name === "sfondo_biblioteca") return <Library />
  if (name === "sfondo_castello") return <Castle />
  if (name === "sfondo_leggenda") return <Legend />
  if (name === "sfondo_spiaggia") return <Beach />
  if (name === "sfondo_spazio") return <Space />

  return (
    <group>
      <Pedestal />
    </group>
  )
}

/**
 * Scena con l'avatar: luci, sfondo e rotazione con il dito/mouse.
 */
export default function AvatarStage({ config, controls = true, focus = "body", height = 460, className, onCreated }) {
  return (
    <div className={className} style={{ height, width: "100%", touchAction: "none" }}>
      <Canvas
        dpr={[1, 2]}
        camera={{ position: [0, 1.15, 3.9], fov: 32 }}
        gl={{ preserveDrawingBuffer: true, antialias: true }}
        onCreated={onCreated}
      >
        <ambientLight intensity={1.3} />
        <directionalLight position={[2.5, 4, 3]} intensity={1.6} />
        <directionalLight position={[-3, 2, -2]} intensity={0.5} color="#b8c4ff" />

        <color attach="background" args={[SKY[config?.equipped?.background] || SKY.default]} />
        <Background name={config?.equipped?.background} />
        <AvatarModel config={config} />

        {controls && (
          <OrbitControls
            makeDefault
            target={[0, 0.88, 0]}
            enablePan={false}
            minDistance={1.1}
            maxDistance={4.5}
            minPolarAngle={PI * 0.3}
            maxPolarAngle={PI * 0.55}
          />
        )}
        {controls && <CameraRig focus={focus} />}
      </Canvas>
    </div>
  )
}
