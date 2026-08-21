import { NavLink, Link } from "react-router-dom"
import css from "./PublicNavbarFooter.module.css"

function PublicNavbar() {
  return (
    <nav className={`navbar navbar-expand-lg navbar-light ${css.publicNavbar}`}>
      <div className="container">

        <Link className={`navbar-brand fw-bold ${css.brand}`} to="/">
          <img
            src="/Midalot.png"
            alt="logo Midalot"
            className={css.logo}
          />

          <span className={css.midalot}>
            Midalot
          </span>
        </Link>

        <button
          className="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#publicNavbar"
          aria-controls="publicNavbar"
          aria-expanded="false"
          aria-label="Apri menu"
        >
          <span className="navbar-toggler-icon"></span>
        </button>

        <div className="collapse navbar-collapse" id="publicNavbar">
          <div className={`ms-auto ${css.publicActions}`}>

            <NavLink to="/training" className={css.navPill}>
              <i className="bi bi-lightning-charge-fill"></i>
              Training
            </NavLink>

            <NavLink to="/chi-siamo" className={css.navPill}>
              <i className="bi bi-info-circle-fill"></i>
              Chi siamo
            </NavLink>

            <Link to="/login" className={`btn ${css.loginBtn}`}>
              <i className="bi bi-box-arrow-in-right"></i>
              Accedi
            </Link>

            <Link to="/register" className={`btn ${css.registerBtn}`}>
              <i className="bi bi-person-plus-fill"></i>
              Registrati
            </Link>

          </div>
        </div>

      </div>
    </nav>
  )
}

export default PublicNavbar
