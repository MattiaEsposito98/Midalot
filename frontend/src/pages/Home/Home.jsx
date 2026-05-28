import { Link } from "react-router-dom"
import css from "./Home.module.css"

function Home() {
  return (
    <section className={css.page}>
      <div className={`container ${css.hero}`}>
        <div className={css.copy}>
          <span className={css.badge}>
            <i className="bi bi-lightning-charge-fill"></i>
            Piattaforma quiz
          </span>

          <h1 className={css.title}>
            Benvenuti sul Midalario!
          </h1>

          <p className={css.subtitle}>
            Quiz assegnati, training per categoria e classifiche: un posto piu'
            vivace dove allenarti, giocare e seguire i tuoi progressi.
          </p>

          <div className={css.actions}>
            <Link to="/training" className={`btn ${css.trainingBtn}`}>
              <i className="bi bi-play-fill"></i>
              Prova training
            </Link>

            <Link to="/login" className={`btn ${css.loginBtn}`}>
              <i className="bi bi-box-arrow-in-right"></i>
              Accedi
            </Link>
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
        <div className={css.featureCard}>
          <span className={css.featureIcon}>
            <i className="bi bi-ui-checks-grid"></i>
          </span>
          <h2>Quiz</h2>
          <p>Trovi subito le sfide disponibili, quelle in corso e quelle completate.</p>
        </div>

        <div className={css.featureCard}>
          <span className={css.featureIcon}>
            <i className="bi bi-lightning-charge-fill"></i>
          </span>
          <h2>Training libero</h2>
          <p>Allenati per categoria anche senza account, con domande estratte a caso.</p>
        </div>

        <div className={css.featureCard}>
          <span className={css.featureIcon}>
            <i className="bi bi-trophy-fill"></i>
          </span>
          <h2>Classifiche</h2>
          <p>Segui i risultati e confronta i punteggi quando la classifica e' attiva.</p>
        </div>
      </div>
    </section>
  )
}

export default Home
