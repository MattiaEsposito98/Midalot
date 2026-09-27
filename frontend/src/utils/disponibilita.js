export function isScaduto(item) {
  return Boolean(item.disponibile_fino_a) && new Date(item.disponibile_fino_a) < new Date()
}

export function formatScadenza(iso) {
  return new Date(iso).toLocaleString("it-IT", {
    weekday: "long",
    day: "2-digit",
    month: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  })
}
