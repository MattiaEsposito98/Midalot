import styles from "./Sfide.module.css"

// Etichetta "Categoria · Sottocategoria" mostrata sotto il testo della domanda.
function QuestionCategory({ category, subcategory }) {
  if (!category) return null

  // La sottocategoria si mostra solo se c'e' ed e' diversa dalla categoria.
  const showSub = subcategory && subcategory.trim().toLowerCase() !== category.trim().toLowerCase()

  return (
    <div className={styles.questionCategory}>
      <i className="bi bi-tag-fill"></i>
      <span>
        <strong>{category}</strong>
        {showSub && (
          <>
            {" "}
            <i className="bi bi-chevron-right"></i> {subcategory}
          </>
        )}
      </span>
    </div>
  )
}

export default QuestionCategory
