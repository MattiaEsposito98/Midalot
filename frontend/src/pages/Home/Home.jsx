import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import css from "./Home.module.css"
import api from "../../service/api"
import { logError } from "../../utils/logger"

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
  const [selectedImage, setSelectedImage] = useState(null)

  useEffect(() => {
    api.get("/showcase")
      .then((res) => setFeedbacks(res.data.feedbacks || []))
      .catch((err) => logError(err))
  }, [])

  return (
    <section className={css.page}>
      <div className={`container ${css.hero}`}>
        <div className={css.copy}>
          {isLoggedIn ? (
            <span className={`${css.badge} ${css.badgeSuccess}`}>
              <i className="bi bi-patch-check-fill"></i>
              Sei dentro Midalot
            </span>
          ) : (
            <span className={css.badge}>
              <i className="bi bi-lightning-charge-fill"></i>
              Piattaforma quiz
            </span>
          )}

          <h1 className={css.title}>
            {isLoggedIn
              ? `Bentornato, ${user?.nickname}!`
              : "Benvenuti sul Midalario!"}
          </h1>

          <p className={css.subtitle}>
            {isLoggedIn
              ? "Da qui puoi riprendere i tuoi quiz assegnati, allenarti col training e controllare lo storico dei tuoi risultati."
              : "Quiz assegnati, training per categoria e classifiche: un posto piu' vivace dove allenarti, giocare e seguire i tuoi progressi."}
          </p>

          <div className={css.actions}>
            {isLoggedIn ? (
              <>
                <Link to="/dashboard" className={`btn ${css.trainingBtn}`}>
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

        <div className={css.visual}>
          <img
            src="/Midalot.png"
            alt="Logo Midalot"
            className={css.heroImage}
          />

        </div>
      </div>

      <div className={`container ${css.featureGrid}`}>
        <FeatureCard
          to={isLoggedIn ? "/dashboard" : null}
          icon="bi-ui-checks-grid"
          title="Quiz One Shot"
          text="Trovi subito le sfide disponibili, quelle in corso e quelle completate."
        />

        <FeatureCard
          to="/training"
          icon="bi-lightning-charge-fill"
          title="Training libero"
          text="Allenati per categoria anche senza account, con domande estratte a caso."
        />

        <FeatureCard
          to={isLoggedIn ? "/storico" : null}
          icon="bi-trophy-fill"
          title="Classifiche"
          text="Segui i risultati e confronta i punteggi quando la classifica e' attiva."
        />
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
    </section>
  )
}

function FeatureCard({ to, icon, title, text }) {
  const content = (
    <>
      <span className={css.featureIcon}>
        <i className={`bi ${icon}`}></i>
      </span>
      <h2>{title}</h2>
      <p>{text}</p>
    </>
  )

  if (!to) {
    return <div className={css.featureCard}>{content}</div>
  }

  return (
    <Link to={to} className={`${css.featureCard} ${css.featureCardLink}`}>
      {content}
    </Link>
  )
}

export default Home
