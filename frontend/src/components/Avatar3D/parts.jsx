import * as THREE from "three"
import { extend } from "@react-three/fiber"
import { Outlines } from "@react-three/drei"
import { RoundedBoxGeometry } from "three/examples/jsm/geometries/RoundedBoxGeometry.js"

extend({ RoundedBoxGeometry })

const OUTLINE = "#1d1b2e"

// Gradiente a 3 toni: da' l'effetto cartoon (ombre "a fasce" invece che sfumate).
let gradient = null
function toonGradient() {
  if (!gradient) {
    gradient = new THREE.DataTexture(new Uint8Array([110, 190, 255]), 3, 1, THREE.RedFormat)
    gradient.minFilter = THREE.NearestFilter
    gradient.magFilter = THREE.NearestFilter
    gradient.needsUpdate = true
  }
  return gradient
}

/**
 * Pezzo del personaggio: una geometria (figlio) con materiale cartoon e contorno.
 * glow: colore emissivo per gli oggetti che brillano (torcia, cristallo, gemme).
 */
export function M({ children, color = "#ffffff", glow, glowIntensity = 0.8, outline = true, opacity, side, ...props }) {
  const transparent = opacity != null && opacity < 1

  return (
    <mesh {...props}>
      {children}
      <meshToonMaterial
        color={color}
        gradientMap={toonGradient()}
        emissive={glow || "#000000"}
        emissiveIntensity={glow ? glowIntensity : 0}
        transparent={transparent}
        opacity={opacity ?? 1}
        side={side ?? THREE.FrontSide}
        depthWrite={!transparent}
      />
      {outline && !transparent && <Outlines thickness={0.012} color={OUTLINE} />}
    </mesh>
  )
}

export const Box = ({ args, radius = 0.03, ...props }) => (
  <M {...props}>
    <roundedBoxGeometry args={[...args, 3, Math.min(radius, Math.min(...args) / 2 - 0.001)]} />
  </M>
)

export const Ball = ({ r, seg = 24, ...props }) => (
  <M {...props}>
    <sphereGeometry args={[r, seg, Math.round(seg * 0.75)]} />
  </M>
)

export const Cyl = ({ rt, rb, h, seg = 20, open = false, start = 0, length = Math.PI * 2, ...props }) => (
  <M {...props}>
    <cylinderGeometry args={[rt, rb ?? rt, h, seg, 1, open, start, length]} />
  </M>
)

export const Cone = ({ r, h, seg = 16, ...props }) => (
  <M {...props}>
    <coneGeometry args={[r, h, seg]} />
  </M>
)

export const Ring = ({ r, tube, arc = Math.PI * 2, seg = 32, ...props }) => (
  <M {...props}>
    <torusGeometry args={[r, tube, 10, seg, arc]} />
  </M>
)

// Calotta sferica: theta 0 = polo nord. Usata per capelli, cappelli ed elmi.
export const Cap = ({ r, theta = Math.PI / 2, phiStart = 0, phiLength = Math.PI * 2, ...props }) => (
  <M side={THREE.DoubleSide} {...props}>
    <sphereGeometry args={[r, 32, 16, phiStart, phiLength, 0, theta]} />
  </M>
)

// Stella a 5 punte piatta (stemmi, cappello del sapiente, bacchetta).
const starShapes = {}
function starGeometryArgs(outer = 0.1, inner = 0.045) {
  const key = `${outer}-${inner}`
  if (!starShapes[key]) {
    const shape = new THREE.Shape()
    for (let i = 0; i < 10; i++) {
      const r = i % 2 === 0 ? outer : inner
      const a = (i / 10) * Math.PI * 2 + Math.PI / 2
      const x = Math.cos(a) * r
      const y = Math.sin(a) * r
      if (i === 0) shape.moveTo(x, y)
      else shape.lineTo(x, y)
    }
    shape.closePath()
    starShapes[key] = shape
  }
  return [starShapes[key], { depth: outer * 0.25, bevelEnabled: false }]
}

// Cuore piatto (occhiali a cuore, aura di cuori).
let heartShape = null
function heartArgs() {
  if (!heartShape) {
    const s = new THREE.Shape()
    s.moveTo(0, -0.5)
    s.bezierCurveTo(-0.15, -0.35, -0.55, -0.1, -0.5, 0.18)
    s.bezierCurveTo(-0.45, 0.45, -0.1, 0.5, 0, 0.25)
    s.bezierCurveTo(0.1, 0.5, 0.45, 0.45, 0.5, 0.18)
    s.bezierCurveTo(0.55, -0.1, 0.15, -0.35, 0, -0.5)
    heartShape = s
  }
  return [heartShape, { depth: 0.25, bevelEnabled: false, curveSegments: 10 }]
}

export const Heart = ({ size = 0.1, ...props }) => (
  <M {...props} scale={size}>
    <extrudeGeometry args={heartArgs()} />
  </M>
)

export const Star = ({ outer = 0.1, inner = 0.045, ...props }) => (
  <M {...props}>
    <extrudeGeometry args={starGeometryArgs(outer, inner)} />
  </M>
)
