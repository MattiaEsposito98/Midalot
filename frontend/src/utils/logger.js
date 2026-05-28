export function logError(...args) {
  if (import.meta.env.DEV) {
    console.error(...args)
  }
}

export function logInfo(...args) {
  if (import.meta.env.DEV) {
    console.log(...args)
  }
}
