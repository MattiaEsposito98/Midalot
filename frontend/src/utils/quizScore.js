export function formatQuizScore(score) {
  if (score == null) return "-"

  return (Number(score) / 100).toLocaleString("it-IT", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}
