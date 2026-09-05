import { useEffect, useMemo, useState } from "react"
import { Link } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import css from "./Home.module.css"
import api from "../../service/api"
import { logError } from "../../utils/logger"
import { formatQuizScore } from "../../utils/quizScore"
import WeeklyLeaderboardBox from "../../components/WeeklyLeaderboardBox/WeeklyLeaderboardBox"
import UserBadge from "../../components/UserBadge/UserBadge"
import ComingSoon from "../../components/ComingSoon/ComingSoon"

const PLACEHOLDER_QUIZZES = [
  { id: "p1", title: "Quiz assegnato" },
  { id: "p2", title: "Quiz assegnato" },
  { id: "p3", title: "Quiz assegnato" },
]

function getQuizStatusLabel(status) {
  if (status === "completed") return "Completato"
  if (status === "in_progress") return "In corso"
  return "Disponibile"
}

function getQuizStatusClass(status) {
  if (status === "completed") return css.statusCompleted
  if (status === "in_progress") return css.statusInProgress
  return css.statusAvailable
}

function getQuizLink(quiz) {
  if (quiz.status === "completed") return `/quiz/${quiz.id}/review`
  return `/quiz/${quiz.id}`
}

function getMidalarioMessage(status) {
  if (status === "open") return "Le iscrizioni sono aperte: partecipa ora!"
  if (status === "closed") return "Iscrizioni chiuse, il quiz sta per iniziare"
  if (status === "running") return "Il quiz e' in corso in questo momento!"
  return ""
}

function FeedbackMarquee({ images, onSelect }) {
  if (images.length === 0) {
    return null
  }

  const shouldScroll = images.length > 2
  const track = shouldScroll ? [...images, ...images] : images

  return (
    <div className={css.marqueeWrap}>
      <div
        className={`${css.marqueeTrack} ${shouldScroll ? "" : css.marqueeStatic}`}
        style={{ "--items": images.length }}
      >
        {track.map((image, index) => (
          <button
            type="button"
            key={`${image.id}-${index}`}
            className={css.imageCard}
            onClick={() => onSelect(image)}
            aria-label={`Apri feedback${image.caption ? ` di ${image.caption}` : ""}`}
          >
            <img src={image.url} alt={image.caption || ""} className={css.image} />
            {image.caption && <span className={css.imageCaption}>{image.caption}</span>}
          </button>
        ))}
      </div>
    </div>
  )
}

function Home() {
  const { user, token } = useAuth()
  const isLoggedIn = !!(user && token)
  const [feedbacks, setFeedbacks] = useState([])
  const [rawQuizzes, setRawQuizzes] = useState([])
  const [trainingCategories, setTrainingCategories] = useState([])
  const [midalarioAnnouncement, setMidalarioAnnouncement] = useState(null)
  const [selectedImage, setSelectedImage] = useState(null)
  const [showAuthModal, setShowAuthModal] = useState(false)

  useEffect(() => {
    api.get("/showcase")
      .then((res) => setFeedbacks(res.data.feedbacks || []))
      .catch((err) => logError(err))

    api.get("/training/categories")
      .then((res) => setTrainingCategories(res.data.categories || []))
      .catch((err) => logError(err))

    api.get("/midalario/announcement")
      .then((res) => setMidalarioAnnouncement(res.data.quiz || null))
      .catch((err) => logError(err))
  }, [])

  useEffect(() => {
    if (!isLoggedIn) return

    api.get("/my-quizzes")
      .then((res) => setRawQuizzes(res.data.quizzes || []))
      .catch((err) => logError(err))
  }, [isLoggedIn])

  const quizzes = useMemo(() => {
    if (!isLoggedIn) return []

    const priority = { in_progress: 0, available: 1, completed: 2 }
    const active = rawQuizzes.filter((q) => q.is_active)

    active.sort((a, b) => (priority[a.status] ?? 99) - (priority[b.status] ?? 99))

    return active.slice(0, 4)
  }, [isLoggedIn, rawQuizzes])

  function handleGuestQuizClick(e) {
    e.preventDefault()
    setShowAuthModal(true)
  }

  const midalarioBannerContent = midalarioAnnouncement && (
    <>
      <span className={css.midalarioBannerIcon}>
        <i className="bi bi-broadcast"></i>
      </span>
      <span className={css.midalarioBannerText}>
        <strong>Il Midalario: {midalarioAnnouncement.title}</strong>
        <span>{getMidalarioMessage(midalarioAnnouncement.status)}</span>
      </span>
      <span className={css.midalarioBannerCta}>
        Scopri di piu'
        <i className="bi bi-arrow-right"></i>
      </span>
    </>
  )

  return (
    <section className={css.page}>
      {midalarioAnnouncement && (
        isLoggedIn ? (
          <Link to="/midalario" className={`container ${css.midalarioBanner}`}>
            {midalarioBannerContent}
          </Link>
        ) : (
          <button
            type="button"
            className={`container ${css.midalarioBanner} ${css.midalarioBannerBtn}`}
            onClick={handleGuestQuizClick}
          >
            {midalarioBannerContent}
          </button>
        )
      )}

      <div className={`container ${css.hero}`}>
        <div className={css.copy}>
          {isLoggedIn && (
            <span className={`${css.badge} ${css.badgeSuccess}`}>
              <i className="bi bi-person-circle"></i>
              {user?.nickname}
            </span>
          )}

          {isLoggedIn && user?.latest_monthly_badge?.label && (
            <UserBadge label={user.latest_monthly_badge.label} />
          )}

          <h1 className={css.title}>
            {isLoggedIn
              ? "Pronto per la prossima sfida?"
              : "Il tuo quiz game, sempre acceso."}
          </h1>

          <p className={css.subtitle}>
            {isLoggedIn ? (
              <>
                Gioca, divertiti, impara e vinci.{" "}
                <strong className={css.prizeHighlight}>
                  Ogni mese in palio nuovi premi per i migliori giocatori.
                </strong>{" "}
                Scala la classifica del training e tieni d'occhio i tuoi progressi. {" "}
                Batti i tuoi avversari e diventa il numero 1 del Midalario.
              </>
            ) : (
              <>
                Sfide a tempo, classifiche in tempo reale e allenamento libero per categoria.{" "}
                <strong className={css.prizeHighlight}>
                  Ogni mese premiamo i migliori giocatori in classifica.
                </strong>{" "}
                Entra nella community mida.lot e mettiti alla prova.
              </>
            )}
          </p>

          <div className={css.actions}>
            {isLoggedIn ? (
              <>
                <Link to="/quiz-one-shot" className={`btn ${css.trainingBtn}`}>
                  <i className="bi bi-grid-1x2-fill"></i>
                  Vai ai Quiz One Shot
                </Link>

                <Link to="/training" className={`btn ${css.loginBtn}`}>
                  <i className="bi bi-play-fill"></i>
                  Training
                </Link>
              </>
            ) : (
              <>
                <Link to="/training" className={`btn ${css.trainingBtn}`}>
                  <i className="bi bi-play-fill"></i>
                  Prova training
                </Link>

                <Link to="/login" className={`btn ${css.loginBtn}`}>
                  <i className="bi bi-box-arrow-in-right"></i>
                  Accedi
                </Link>
              </>
            )}
          </div>
        </div>

        <div className={css.heroLeaderboard}>
          <WeeklyLeaderboardBox />
        </div>
      </div>

      <div className={`container ${css.carouselSection}`}>
        <div className={css.sectionHeader}>
          <div>
            <span className={css.sectionBadge}>Quiz One Shot</span>
            <h2 className={css.sectionTitle}>Le tue sfide a tempo</h2>
          </div>

          {isLoggedIn ? (
            <Link to="/quiz-one-shot" className={css.seeMoreLink}>
              Vedi altro
              <i className="bi bi-arrow-right"></i>
            </Link>
          ) : (
            <button type="button" className={css.seeMoreLink} onClick={handleGuestQuizClick}>
              Vedi altro
              <i className="bi bi-arrow-right"></i>
            </button>
          )}
        </div>

        <div className={css.carouselTrack}>
          {isLoggedIn ? (
            quizzes.length > 0 ? (
              quizzes.map((quiz) => (
                <Link to={getQuizLink(quiz)} key={quiz.id} className={css.quizCard}>
                  <span className={`${css.statusBadge} ${getQuizStatusClass(quiz.status)}`}>
                    {getQuizStatusLabel(quiz.status)}
                  </span>
                  <h3 className={css.quizCardTitle}>{quiz.title}</h3>
                  <p className={css.quizCardMeta}>
                    {quiz.questions_count} domande
                    {quiz.status === "completed" && quiz.score != null && (
                      <> · {formatQuizScore(quiz.score)} punti</>
                    )}
                  </p>
                </Link>
              ))
            ) : (
              <ComingSoon
                compact
                icon="bi-grid-1x2-fill"
                title="Presto in arrivo!"
                message="Nuovi Quiz One Shot in arrivo a breve."
              />
            )
          ) : (
            PLACEHOLDER_QUIZZES.map((quiz) => (
              <button
                type="button"
                key={quiz.id}
                className={`${css.quizCard} ${css.quizCardLocked}`}
                onClick={handleGuestQuizClick}
              >
                <span className={css.lockIcon}>
                  <i className="bi bi-lock-fill"></i>
                </span>
                <h3 className={css.quizCardTitle}>{quiz.title}</h3>
                <p className={css.quizCardMeta}>Iscriviti/Accedi per Sbloccare i Quiz a Classifica</p>
              </button>
            ))
          )}
        </div>
      </div>

      <div className={`container ${css.carouselSection}`}>
        <div className={css.sectionHeader}>
          <div>
            <span className={css.sectionBadge}>Training</span>
            <h2 className={css.sectionTitle}>Allenati per categoria</h2>
          </div>

          <Link to="/training" className={css.seeMoreLink}>
            Vedi altro
            <i className="bi bi-arrow-right"></i>
          </Link>
        </div>

        <div className={css.carouselTrack}>
          {trainingCategories.length > 0 ? (
            trainingCategories.map((category) => (
              <Link to={`/training/${category.slug}`} key={category.id} className={css.trainingCard}>
                {category.image && (
                  <div className={css.trainingCardImage}>
                    <img src={category.image} alt="" />
                  </div>
                )}
                <div className={css.trainingCardBody}>
                  <h3 className={css.quizCardTitle}>{category.name}</h3>
                  <p className={css.quizCardMeta}>
                    {category.description || "Training disponibili per questa categoria."}
                  </p>
                </div>
              </Link>
            ))
          ) : (
            <div className={css.emptyCarousel}>Nessuna categoria disponibile al momento.</div>
          )}
        </div>
      </div>

      {feedbacks.length > 0 && (
        <div className={`container ${css.feedbackSection}`}>
          <span className={css.sectionBadge}>Feedback</span>
          <h2 className={css.sectionTitle}>Cosa dice la community</h2>

          <FeedbackMarquee images={feedbacks} onSelect={setSelectedImage} />
        </div>
      )}

      {selectedImage && (
        <div className={css.lightbox} onClick={() => setSelectedImage(null)}>
          <div className={css.lightboxContent} onClick={(e) => e.stopPropagation()}>
            <button
              type="button"
              className={css.lightboxClose}
              onClick={() => setSelectedImage(null)}
              aria-label="Chiudi"
            >
              <i className="bi bi-x-lg"></i>
            </button>
            <img src={selectedImage.url} alt={selectedImage.caption || ""} className={css.lightboxImage} />
          </div>
        </div>
      )}

      {showAuthModal && (
        <div className={css.lightbox} onClick={() => setShowAuthModal(false)}>
          <div className={css.authModal} onClick={(e) => e.stopPropagation()}>
            <button
              type="button"
              className={css.lightboxClose}
              onClick={() => setShowAuthModal(false)}
              aria-label="Chiudi"
            >
              <i className="bi bi-x-lg"></i>
            </button>

            <span className={css.lockIcon}>
              <i className="bi bi-lock-fill"></i>
            </span>

            <h3 className={css.authModalTitle}>Accedi o registrati</h3>
            <p className={css.authModalText}>
              Per vedere e partecipare ai quiz devi avere un account Midalot.
            </p>

            <div className={css.authModalActions}>
              <Link to="/login" className={`btn ${css.loginBtn}`}>
                <i className="bi bi-box-arrow-in-right"></i>
                Accedi
              </Link>

              <Link to="/register" className={`btn ${css.trainingBtn}`}>
                <i className="bi bi-person-plus-fill"></i>
                Registrati
              </Link>
            </div>
          </div>
        </div>
      )}
    </section>
  )
}

export default Home
