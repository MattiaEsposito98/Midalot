import { Link } from "react-router-dom"
import css from "./PublicNavbarFooter.module.css"

function PublicNavbar() {
  return (
    <nav className="navbar navbar-expand-lg navbar-light border-bottom shadow-sm"
      style={{ backgroundColor: "#ffc107" }}>
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

        <div className="ms-auto d-flex gap-2">

          <Link to="/login" className={`btn ${css.loginBtn}`}>
            Accedi
          </Link>

          <Link to="/register" className={`btn ${css.registerBtn}`}>
            Registrati
          </Link>

        </div>

      </div>
    </nav>
  )
}

export default PublicNavbar