import { Link } from "react-router-dom"
import css from "./PublicNavbarFooter.module.css"

function PublicNavbar() {
  return (
    <nav className="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
      <div className="container">

        <Link className="navbar-brand fw-bold" to="/">
          <img
            src="/Midalot.jpg"
            alt="logo Midalot"
            className={css.logo}
          />
        </Link>

        <div className="ms-auto d-flex gap-2">

          <Link to="/login" className="btn btn-outline-primary">
            Accedi
          </Link>

          <Link to="/register" className="btn btn-primary">
            Registrati
          </Link>

        </div>

      </div>
    </nav>
  )
}

export default PublicNavbar