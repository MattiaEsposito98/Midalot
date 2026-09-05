import { NavLink, Link, useNavigate } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import css from "./PublicNavbarFooter.module.css"
import UserBadge from "../UserBadge/UserBadge"

function PublicNavbar() {
  const { user, token, logout } = useAuth()
  const navigate = useNavigate()
  const isLoggedIn = !!(user && token)

  const handleLogout = () => {
    logout()
    navigate("/")
  }

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

            {isLoggedIn ? (
              <>
                <NavLink to="/midalario" className={css.navPill}>
                  <i className="bi bi-broadcast"></i>
                  Il Midalario
                </NavLink>

                <NavLink to="/quiz-one-shot" className={css.navPill}>
                  <i className="bi bi-grid-1x2-fill"></i>
                  Quiz One Shot
                </NavLink>

                <NavLink to="/training" className={css.navPill}>
                  <i className="bi bi-lightning-charge-fill"></i>
                  Training
                </NavLink>

                <NavLink to="/minigiochi" className={css.navPill}>
                  <i className="bi bi-joystick"></i>
                  Minigiochi
                </NavLink>

                <div className="dropdown">
                  <a
                    className={`${css.profileBtn} dropdown-toggle`}
                    href="#"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i className="bi bi-person-circle"></i>
                    {user?.name || user?.nickname || "Profilo"}
                    <UserBadge label={user?.latest_monthly_badge?.label} />
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
                </div>
              </>
            ) : (
              <>
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
              </>
            )}

          </div>
        </div>

      </div>
    </nav>
  )
}

export default PublicNavbar
