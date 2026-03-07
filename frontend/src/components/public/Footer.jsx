import css from "./PublicNavbarFooter.module.css"
import { Link } from "react-router-dom"

function Footer() {
  return (
    <footer className="bg-dark text-white py-4 mt-auto">

      <div className="container text-center">

        {/* Logo + nome */}
        <div className="d-flex justify-content-center align-items-center gap-2 mb-2">
          <Link className="navbar-brand fw-bold m-0" to="/">
            <img
              src="/Midalot-footer.jpeg"
              alt="logo Midalot"
              className={css.logo}
            />
          </Link>

          <span className="fw-semibold">Midalot</span>
        </div>

        {/* Copyright */}
        <small>
          © {new Date().getFullYear()} Tutti i diritti riservati
        </small>

      </div>

    </footer>
  )
}

export default Footer