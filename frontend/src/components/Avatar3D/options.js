// Opzioni di aspetto dell'avatar (gratis, sempre disponibili) e configurazione di partenza.

export const SKIN_TONES = ["#ffe0c7", "#f6c9a4", "#e2a77c", "#c68652", "#8d5a3b", "#5c3a24"]
export const HAIR_COLORS = [
  "#14100c", // nero
  "#2b1d14", // castano scuro
  "#5a3825", // castano
  "#a0662c", // nocciola
  "#e8c26a", // biondo
  "#f3e2a9", // biondo platino
  "#d2462e", // rosso
  "#8a8a96", // grigio
  "#f4f4f6", // bianco
  "#3a6be0", // blu
  "#2f9b5a", // verde
  "#8a4be0", // viola
  "#e05fb0", // rosa
]
export const EYE_COLORS = ["#2b1d14", "#2f6fd0", "#2f9b5a", "#7a4a20"]

// Le acconciature sono per tutti (ragazzo e ragazza).
export const HAIR_STYLES = [
  { key: "corto", label: "Corti", icon: "bi-person" },
  { key: "riga", label: "Con la riga", icon: "bi-slash-lg" },
  { key: "spettinato", label: "Spettinati", icon: "bi-lightning" },
  { key: "ricci", label: "Ricci", icon: "bi-record-circle" },
  { key: "cresta", label: "Cresta", icon: "bi-triangle" },
  { key: "afro", label: "Afro", icon: "bi-cloud" },
  { key: "caschetto", label: "Caschetto", icon: "bi-circle-half" },
  { key: "lungo", label: "Lunghi", icon: "bi-water" },
  { key: "coda", label: "Coda", icon: "bi-bezier2" },
  { key: "codini", label: "Codini", icon: "bi-three-dots" },
  { key: "chignon", label: "Chignon", icon: "bi-circle" },
  { key: "treccia", label: "Treccia", icon: "bi-link" },
  { key: "rasato", label: "Rasati", icon: "bi-dash-circle" },
]

// Capelli di partenza quando si sceglie il personaggio.
export const DEFAULT_HAIR = { m: "corto", f: "lungo" }

export const BROWS = [
  { key: "morbide", label: "Morbide" },
  { key: "folte", label: "Folte" },
  { key: "sottili", label: "Sottili" },
  { key: "arcuate", label: "Arcuate" },
]

export const MOUTHS = [
  { key: "sorriso", label: "Sorriso" },
  { key: "sorrisone", label: "Sorrisone" },
  { key: "furbetto", label: "Furbetto" },
  { key: "neutro", label: "Neutro" },
]

export const BEARDS = [
  { key: "nessuna", label: "Nessuna" },
  { key: "baffi", label: "Baffi" },
  { key: "pizzetto", label: "Pizzetto" },
  { key: "corta", label: "Barba corta" },
  { key: "folta", label: "Barba folta" },
]

// Vestiti base sempre posseduti (livello 1): il corpo non resta mai "nudo".
export const BASE_ITEMS = { torso: "maglietta_bianca", legs: "jeans", feet: "sneakers" }

export const DEFAULT_AVATAR = {
  gender: "m",
  skin: SKIN_TONES[1],
  faceShape: "ovale",
  brows: "morbide",
  mouth: "sorriso",
  beard: "nessuna",
  freckles: false,
  hair: "corto",
  hairColor: "#5a3825",
  eyes: EYE_COLORS[0],
  equipped: { ...BASE_ITEMS },
  itemColors: {},
  emblem: { bg: "#2f5fd0", fg: "#ffd54a", symbol: "star" },
}
