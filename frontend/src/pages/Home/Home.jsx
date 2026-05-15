import { Link } from "react-router-dom"
import css from "./Home.module.css"

function Home() {
  return (
    <section className={css.hero}>
      <div className="container">
        <div className="row align-items-center justify-content-center g-5">

          <div className="col-lg-6">
            <span className={css.badge}>
              🚀 Piattaforma Quiz
            </span>

            <h1 className={css.title}>
              Benvenuto su Midalot
            </h1>

            <p className={css.subtitle}>
              Accedi, completa i quiz assegnati e monitora i tuoi risultati
              in modo semplice, moderno e veloce.
            </p>

            <div className="d-flex flex-wrap gap-3">
              <Link to="/login" className={`btn ${css.loginBtn}`}>
                Accedi
              </Link>

              <Link to="/register" className={`btn ${css.registerBtn}`}>
                Registrati
              </Link>
            </div>
          </div>

          <div className="col-lg-5 text-center">
            <img
              src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1200&auto=format&fit=crop"
              alt="Quiz platform"
              className={css.heroImage}
            />
          </div>

        </div>
      </div>
    </section>
  )
}

export default Home