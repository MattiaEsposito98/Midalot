import * as THREE from "three"

/*
 * Forma della testa. La meta' alta e' sempre una calotta di raggio 0.3 (cosi'
 * capelli, cappelli ed elmi calzano su tutte le forme); cambia la meta' bassa:
 * guance, mascella e mento. Il mento viene anche spinto un po' in avanti.
 */

export const HEAD_R = 0.3
const DEPTH = 0.93 // viso leggermente schiacciato davanti/dietro
const JUT = 0.16 // quanto il mento sporge in avanti (per unita' di altezza sotto il centro)

// Punti (raggio, altezza) dal mento all'equatore della testa.
const LOWER = {
  ovale: [
    [0.0, -0.345],
    [0.11, -0.335],
    [0.2, -0.29],
    [0.262, -0.2],
    [0.292, -0.095],
  ],
  squadrato: [
    [0.0, -0.33],
    [0.15, -0.327],
    [0.235, -0.295],
    [0.278, -0.215],
    [0.297, -0.105],
  ],
  cuore: [
    [0.0, -0.35],
    [0.06, -0.343],
    [0.15, -0.29],
    [0.235, -0.195],
    [0.285, -0.085],
  ],
  paffuto: [
    [0.0, -0.31],
    [0.14, -0.302],
    [0.245, -0.255],
    [0.3, -0.16],
    [0.31, -0.065],
  ],
}

export const FACE_SHAPES = [
  { key: "ovale", label: "Ovale" },
  { key: "squadrato", label: "Squadrato" },
  { key: "cuore", label: "A cuore" },
  { key: "paffuto", label: "Paffuto" },
]

const profiles = {}

// Profilo completo campionato (dal mento alla cima), come Vector2(raggio, y).
export function headProfile(shape = "ovale") {
  if (!profiles[shape]) {
    const lower = (LOWER[shape] || LOWER.ovale).map(([r, y]) => new THREE.Vector2(r, y))
    const top = []
    for (let i = 0; i <= 10; i++) {
      const t = (i / 10) * (Math.PI / 2)
      top.push(new THREE.Vector2(HEAD_R * Math.cos(t), HEAD_R * Math.sin(t)))
    }

    const curve = new THREE.SplineCurve([...lower, ...top])
    const points = curve.getSpacedPoints(48)
    points[0].x = 0
    points[points.length - 1].x = 0
    profiles[shape] = points
  }
  return profiles[shape]
}

// Raggio della testa all'altezza y.
export function radiusAt(shape, y) {
  const p = headProfile(shape)
  if (y <= p[0].y) return 0
  for (let i = 1; i < p.length; i++) {
    if (y <= p[i].y) {
      const a = p[i - 1]
      const b = p[i]
      const k = (y - a.y) / (b.y - a.y || 1)
      return a.x + (b.x - a.x) * k
    }
  }
  return 0
}

const jut = (y) => (y < 0 ? -y * JUT : 0)

/** Profondita' (z) della superficie del viso nel punto (x, y): ci si appoggiano occhi, naso, bocca... */
export function faceZ(shape, x, y) {
  const r = radiusAt(shape, y)
  return Math.sqrt(Math.max(0, r * r - x * x)) * DEPTH + jut(y)
}

const geometries = {}

/**
 * Geometria della testa (o di un suo "guscio": barba, ecc.).
 * yMax: taglia sopra questa altezza. grow: gonfia il guscio. phi: solo la parte davanti.
 */
export function headGeometry(shape = "ovale", { yMax = 1, grow = 0, phiStart = 0, phiLength = Math.PI * 2 } = {}) {
  const key = [shape, yMax, grow, phiStart, phiLength].join("|")
  if (!geometries[key]) {
    let points = headProfile(shape).filter((p) => p.y <= yMax)
    if (grow) points = points.map((p) => new THREE.Vector2(p.x + grow, p.y - grow * 0.3))

    // Lathe: phi = 0 guarda verso +z (davanti).
    const geo = new THREE.LatheGeometry(points, 48, phiStart, phiLength)
    const pos = geo.attributes.position
    for (let i = 0; i < pos.count; i++) {
      const y = pos.getY(i)
      pos.setZ(i, pos.getZ(i) * DEPTH + jut(y))
    }
    geo.computeVertexNormals()
    geometries[key] = geo
  }
  return geometries[key]
}
