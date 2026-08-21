import { Link } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import css from "./Home.module.css"

function Home() {
  const { user, token } = useAuth()
  const isLoggedIn = !!(user && token)

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
                  Vai alla dashboard
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
          title="Quiz"
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
