// Colori degli oggetti con tavolozza. Le chiavi (oggetto e colore) devono
// combaciare con backend/config/avatar.php -> palettes, che decide da quale
// livello ogni colore e' disponibile. Il primo colore e' quello di partenza.
// Ogni colore contiene i campi che l'oggetto usa: shirt / pants / shoe+sole / color+visor.

export const PALETTES = {
  maglietta_bianca: [
    { key: "bianco", label: "Bianco", shirt: "#f4f4f6" },
    { key: "rosso", label: "Rosso", shirt: "#e23b3b" },
    { key: "blu", label: "Blu", shirt: "#2f6fd0" },
    { key: "verde", label: "Verde", shirt: "#2f9b5a" },
    { key: "giallo", label: "Giallo", shirt: "#f2c230" },
    { key: "rosa", label: "Rosa", shirt: "#ff8fc0" },
    { key: "nero", label: "Nero", shirt: "#2b2b3a" },
    { key: "viola", label: "Viola", shirt: "#8a4be0" },
    { key: "arancione", label: "Arancione", shirt: "#f08a24" },
    { key: "azzurro", label: "Azzurro", shirt: "#5ec8f2" },
    { key: "oro", label: "Oro", shirt: "#e6b422" },
  ],
  jeans: [
    { key: "blu", label: "Blu", pants: "#3d5f9e" },
    { key: "nero", label: "Nero", pants: "#2b2b3a" },
    { key: "grigio", label: "Grigio", pants: "#7a7f8f" },
    { key: "chiaro", label: "Chiaro", pants: "#8fb3e0" },
    { key: "bianco", label: "Bianco", pants: "#e8e8ee" },
    { key: "bordeaux", label: "Bordeaux", pants: "#8c2f3c" },
  ],
  sneakers: [
    { key: "classiche", label: "Classiche", shoe: "#ffffff", sole: "#e23b3b" },
    { key: "nere", label: "Nere", shoe: "#2b2b3a", sole: "#ffffff" },
    { key: "blu", label: "Blu", shoe: "#2f6fd0", sole: "#ffffff" },
    { key: "verdi", label: "Verdi", shoe: "#2f9b5a", sole: "#ffffff" },
    { key: "rosa", label: "Rosa", shoe: "#ff8fc0", sole: "#c2185b" },
    { key: "gialle", label: "Gialle", shoe: "#f2c230", sole: "#2b2b3a" },
  ],
  cappellino: [
    { key: "rosso", label: "Rosso", color: "#e23b3b", visor: "#c42828" },
    { key: "blu", label: "Blu", color: "#2f6fd0", visor: "#1f4fa8" },
    { key: "verde", label: "Verde", color: "#2f9b5a", visor: "#1f7a42" },
    { key: "nero", label: "Nero", color: "#2b2b3a", visor: "#16161f" },
    { key: "rosa", label: "Rosa", color: "#ff8fc0", visor: "#d9609a" },
    { key: "giallo", label: "Giallo", color: "#f2c230", visor: "#c99a12" },
    { key: "viola", label: "Viola", color: "#8a4be0", visor: "#6a2fc0" },
  ],
  gonna_rossa: [
    { key: "rosso", label: "Rosso", pants: "#e23b3b" },
    { key: "blu", label: "Blu", pants: "#2f6fd0" },
    { key: "nero", label: "Nero", pants: "#2b2b3a" },
    { key: "rosa", label: "Rosa", pants: "#ff8fc0" },
    { key: "verde", label: "Verde", pants: "#2f9b5a" },
    { key: "viola", label: "Viola", pants: "#8a4be0" },
    { key: "giallo", label: "Giallo", pants: "#f2c230" },
  ],
}

// Colore scelto (o quello di partenza) per un oggetto.
export function variantFor(itemKey, itemColors) {
  const palette = PALETTES[itemKey]
  if (!palette) return null
  return palette.find((c) => c.key === itemColors?.[itemKey]) || palette[0]
}

// Esadecimale principale di un colore (per i pallini del selettore).
export function swatchColor(variant) {
  return variant.shirt || variant.pants || variant.shoe || variant.color || "#cccccc"
}
