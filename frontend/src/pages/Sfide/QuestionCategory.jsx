import styles from "./Sfide.module.css"

const same = (a, b) => a.trim().toLowerCase() === b.trim().toLowerCase()

// "Training Disney - Marvel" -> "Marvel": la categoria e' gia' scritta prima.
function shortTraining(title, category) {
  let t = title.trim().replace(/^training\s+/i, "")
  const prefix = `${category.trim()} - `
  if (t.toLowerCase().startsWith(prefix.toLowerCase())) t = t.slice(prefix.length)
  return t.trim() || title
}

// Etichetta "Categoria › Sottocategoria › Training" mostrata sotto il testo della domanda.
// Le voci vuote o uguali alla precedente (es. sottocategoria con lo stesso nome
// della categoria) non si ripetono.
function QuestionCategory({ category, subcategory, training }) {
  if (!category) return null

  const parts = [category]
  for (const part of [subcategory, training && shortTraining(training, category)]) {
    if (part && !parts.some((p) => same(p, part))) parts.push(part)
  }

  return (
    <div className={styles.questionCategory}>
      <i className="bi bi-tag-fill"></i>
      <span>
        <strong>{parts[0]}</strong>
        {parts.slice(1).map((part) => (
          <span key={part}>
            {" "}
            <i className="bi bi-chevron-right"></i> {part}
          </span>
        ))}
      </span>
    </div>
  )
}

export default QuestionCategory
