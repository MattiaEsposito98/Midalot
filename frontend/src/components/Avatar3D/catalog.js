// Zone dell'avatar e rarita' (dati, senza grafica).

export const SLOTS = [
  { key: "head", label: "Testa", icon: "bi-emoji-sunglasses" },
  { key: "face", label: "Occhiali", icon: "bi-eyeglasses" },
  { key: "torso", label: "Maglia", icon: "bi-person-standing" },
  { key: "armor", label: "Armatura", icon: "bi-shield-shaded" },
  { key: "neck", label: "Collo", icon: "bi-wind" },
  { key: "hands", label: "Guanti", icon: "bi-hand-index" },
  { key: "legs", label: "Pantaloni", icon: "bi-person-walking" },
  { key: "feet", label: "Scarpe", icon: "bi-shoe" },
  { key: "handR", label: "Mano destra", icon: "bi-magic" },
  { key: "handL", label: "Mano sinistra", icon: "bi-book" },
  { key: "back", label: "Schiena", icon: "bi-backpack" },
  { key: "pet", label: "Compagno", icon: "bi-heart" },
  { key: "aura", label: "Aura", icon: "bi-stars" },
  { key: "background", label: "Sfondo", icon: "bi-image" },
  { key: "title", label: "Titolo", icon: "bi-patch-check" },
  { key: "frame", label: "Cornice", icon: "bi-circle-square" },
]

// Colore del cielo di ogni sfondo (scena 3D e ritratto).
export const SKY = {
  default: "#ece9ff",
  sfondo_foresta: "#bfe6ff",
  sfondo_biblioteca: "#4a3326",
  sfondo_castello: "#ffd8a8",
  sfondo_leggenda: "#241a47",
  sfondo_spiaggia: "#9fe0ff",
  sfondo_spazio: "#0d1030",
}

export const RARITY = {
  base: { label: "Base", color: "#8a8fa3" },
  comune: { label: "Comune", color: "#6c7a92" },
  raro: { label: "Raro", color: "#2f7de1" },
  epico: { label: "Epico", color: "#9b4dde" },
  leggendario: { label: "Leggendario", color: "#f0a020" },
}
