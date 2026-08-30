import { useState } from "react"
import { Link, useParams } from "react-router-dom"
import { DndContext, closestCenter, PointerSensor, useSensor, useSensors } from "@dnd-kit/core"
import { SortableContext, arrayMove, useSortable, verticalListSortingStrategy } from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import shared from "./TastieraRotta.module.css"
import styles from "./SaltoTemporale.module.css"
import { formatQuizScore } from "../../utils/quizScore"
import { useMinigiocoAttempt } from "../../hooks/useMinigiocoAttempt"

function SortableItem({ item, index, disabled }) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: item.id,
    disabled,
  })

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  }

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={`${styles.itemCard} ${isDragging ? styles.itemCardDragging : ""}`}
      {...attributes}
      {...listeners}
    >
      <span className={styles.itemRank}>{index + 1}</span>

      {item.image && (
        <div className={styles.itemImage}>
          <img src={item.image} alt="" />
        </div>
      )}

      <span className={styles.itemLabel}>{item.label}</span>

      <i className={`bi bi-grip-vertical ${styles.dragHandle}`}></i>
    </div>
  )
}

function SaltoTemporale() {
  const { id } = useParams()

  const {
    minigioco,
    currentRound,
    currentIndex,
    loading,
    error,
    result,
    submitting,
    roundLocked,
    feedback,
    timeLeft,
    submitAnswer,
    handleBackToMinigiochi,
  } = useMinigiocoAttempt(id, { retryOnWrong: false })

  const [order, setOrder] = useState(currentRound?.items || [])
  const [seenRoundId, setSeenRoundId] = useState(currentRound?.id)

  if (currentRound?.id !== seenRoundId) {
    setSeenRoundId(currentRound?.id)
    setOrder(currentRound?.items || [])
  }

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 8 } })
  )

  function handleDragEnd(event) {
    const { active, over } = event

    if (!over || active.id === over.id) return

    setOrder((items) => {
      const oldIndex = items.findIndex((item) => item.id === active.id)
      const newIndex = items.findIndex((item) => item.id === over.id)
      return arrayMove(items, oldIndex, newIndex)
    })
  }

  async function handleConfirm() {
    if (submitting || roundLocked || order.length === 0) return
    await submitAnswer(order.map((item) => item.id))
  }

  function formatSeconds(seconds) {
    return `${seconds}s`
  }

  if (loading) {
    return (
      <div className={shared.centerBox}>
        <div className="spinner-border text-primary"></div>
        <p className={shared.centerText}>Caricamento minigioco...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className={shared.centerBox}>
        <div className={shared.errorCard}>
          <h2 className={shared.errorTitle}>Attenzione</h2>
          <p className={shared.errorText}>{error}</p>
          <button className="btn btn-primary" onClick={handleBackToMinigiochi}>
            Torna ai Minigiochi
          </button>
        </div>
      </div>
    )
  }

  if (result) {
    return (
      <div className={shared.centerBox}>
        <div className={shared.resultCard}>
          <div className={shared.resultIcon}>
            <i className="bi bi-trophy-fill"></i>
          </div>
          <h1 className={shared.resultTitle}>Minigioco completato</h1>
          <p className={shared.resultSubtitle}>
            Hai terminato tutte le domande del minigioco.
          </p>
          <div className={shared.scoreBox}>
            <span className={shared.scoreLabel}>Punteggio finale</span>
            <strong className={shared.scoreValue}>{formatQuizScore(result.score)}</strong>
          </div>
          <button className="btn btn-primary" onClick={handleBackToMinigiochi}>
            Torna ai Minigiochi
          </button>
          <Link to={`/minigiochi/${id}/review`} className="btn btn-outline-primary">
            <i className="bi bi-clipboard-check"></i>
            Rivedi il minigioco
          </Link>
          {minigioco?.leaderboard_visible && (
            <Link to={`/minigiochi/${id}/leaderboard`} className="btn btn-warning">
              <i className="bi bi-trophy-fill"></i>
              Classifica
            </Link>
          )}
        </div>
      </div>
    )
  }

  if (!minigioco || !currentRound) return null

  const progressPercent = ((currentIndex + 1) / minigioco.total_rounds) * 100
  const timePercent =
    Number(currentRound.time_limit_seconds) > 0
      ? Math.max(0, (timeLeft / Number(currentRound.time_limit_seconds)) * 100)
      : 0
  const locked = submitting || roundLocked

  return (
    <div className={shared.page}>
      <div className="container">
        <div className={shared.topBar}>
          <div>
            <h1 className={shared.quizTitle}>{minigioco.title}</h1>
            <p className={shared.quizSubtitle}>
              Domanda {currentIndex + 1} di {minigioco.total_rounds}
            </p>
          </div>

          <div className={shared.timerCard}>
            <span className={shared.timerLabel}>Tempo rimasto</span>
            <strong className={shared.timerValue}>{formatSeconds(timeLeft)}</strong>
          </div>
        </div>

        <div className={shared.progressWrap}>
          <div className={shared.progressLabelRow}>
            <span>Avanzamento minigioco</span>
            <span>{Math.round(progressPercent)}%</span>
          </div>

          <div className={shared.progressBar}>
            <div className={shared.progressFill} style={{ width: `${progressPercent}%` }} />
          </div>
        </div>

        <div className={shared.questionCard}>
          <div className={shared.timeBarWrap}>
            <div className={shared.timeBar}>
              <div className={shared.timeBarFill} style={{ width: `${timePercent}%` }} />
            </div>
          </div>

          <div className={styles.instructions}>
            <i className="bi bi-arrow-down-up"></i>
            Trascina per ordinare cronologicamente, dal più vecchio al più recente
          </div>

          {feedback && (
            <div
              className={`${shared.feedback} ${
                feedback.type === "correct" ? shared.feedbackCorrect : shared.feedbackWrong
              }`}
            >
              {feedback.message}
            </div>
          )}

          <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
            <SortableContext items={order.map((item) => item.id)} strategy={verticalListSortingStrategy}>
              <div className={styles.itemList}>
                {order.map((item, index) => (
                  <SortableItem key={item.id} item={item} index={index} disabled={locked} />
                ))}
              </div>
            </SortableContext>
          </DndContext>

          <button
            type="button"
            className={`btn btn-primary ${styles.confirmBtn}`}
            onClick={handleConfirm}
            disabled={locked || order.length === 0}
          >
            Conferma ordine
          </button>
        </div>
      </div>
    </div>
  )
}

export default SaltoTemporale
