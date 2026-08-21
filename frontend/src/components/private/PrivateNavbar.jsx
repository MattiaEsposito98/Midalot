import { Link, NavLink, useNavigate } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import css from "./PrivateNavbar.module.css"

function PrivateNavbar() {
  const { logout, user } = useAuth()
  const navigate = useNavigate()

  const handleLogout = () => {
    logout()
    navigate("/")
  }

  return (
    <nav className={`navbar navbar-expand-lg navbar-light border-bottom shadow-sm ${css.navbar}`}>
      <div className="container">
        <Link className={`navbar-brand fw-bold ${css.brand}`} to="/">
          <img
            src="/Midalot.png"
            alt="logo Midalot"
            className={css.logo}
          />

          <span className={css.brandName}>
            Midalot
          </span>
        </Link>

        <button
          className="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#privateNavbar"
          aria-controls="privateNavbar"
          aria-expanded="false"
          aria-label="Apri menu"
        >
          <span className="navbar-toggler-icon"></span>
        </button>

        <div className="collapse navbar-collapse" id="privateNavbar">
          <ul className="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            <li className="nav-item">
              <NavLink className={({ isActive }) => `${css.navLink} ${isActive ? css.activeLink : ""}`} to="/midalario">
                <i className="bi bi-broadcast"></i>
                Il Midalario
              </NavLink>
            </li>

            <li className="nav-item">
              <NavLink className={({ isActive }) => `${css.navLink} ${isActive ? css.activeLink : ""}`} to="/quiz-one-shot">
                <i className="bi bi-grid-1x2-fill"></i>
                Quiz One Shot
              </NavLink>
            </li>

            <li className="nav-item">
              <NavLink className={({ isActive }) => `${css.navLink} ${isActive ? css.activeLink : ""}`} to="/training">
                <i className="bi bi-lightning-charge-fill"></i>
                Training
              </NavLink>
            </li>

            <li className="nav-item dropdown ms-lg-3">
              <a
                className={`${css.profileBtn} dropdown-toggle`}
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <i className="bi bi-person-circle"></i>
                {user?.name || user?.username || "Profilo"}
              </a>

              <ul className={`dropdown-menu dropdown-menu-end shadow-sm border-0 ${css.profileMenu}`}>
                <li>
                  <Link className="dropdown-item" to="/profilo">
                    <i className="bi bi-person-lines-fill me-2"></i>
                    Il mio profilo
                  </Link>
                </li>

                <li>
                  <Link className="dropdown-item" to="/storico">
                    <i className="bi bi-clock-history me-2"></i>
                    Storico
                  </Link>
                </li>

                <li>
                  <Link className="dropdown-item" to="/chi-siamo">
                    <i className="bi bi-info-circle-fill me-2"></i>
                    Chi siamo
                  </Link>
                </li>

                <li>
                  <Link className="dropdown-item" to="/regolamento">
                    <i className="bi bi-journal-check me-2"></i>
                    Regolamento
                  </Link>
                </li>

                <li>
                  <hr className="dropdown-divider" />
                </li>

                <li>
                  <button
                    onClick={handleLogout}
                    className="dropdown-item text-danger"
                  >
                    <i className="bi bi-box-arrow-right me-2"></i>
                    Logout
                  </button>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  )
}

export default PrivateNavbar
