import { NavLink, Link, useNavigate } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import css from "./PublicNavbarFooter.module.css"
import UserBadge from "../UserBadge/UserBadge"
import XpBar from "../XpBar/XpBar"
import UserAvatar from "../UserAvatar/UserAvatar"
import { useChallengeCount } from "../../hooks/useChallengeCount"

function PublicNavbar() {
  const { user, token, logout } = useAuth()
  const navigate = useNavigate()
  const isLoggedIn = !!(user && token)
  const challengeCount = useChallengeCount(isLoggedIn)

  const handleLogout = () => {
    logout()
    navigate("/")
  }

  return (
    <nav className={`navbar ${isLoggedIn ? "navbar-expand-xl" : "navbar-expand-lg"} navbar-light ${css.publicNavbar} ${isLoggedIn ? css.loggedIn : ""}`}>
      <div className={isLoggedIn ? "container-fluid px-3 px-xl-4" : "container"}>

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

        {isLoggedIn && (
          <div className="w-100 d-xl-none">
            <XpBar variant="navbar" />
          </div>
        )}

        <div className="collapse navbar-collapse" id="publicNavbar">
          {isLoggedIn && (
            <div className="d-none d-xl-flex flex-grow-1 justify-content-center">
              <XpBar variant="navbar" />
            </div>
          )}

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

                <NavLink to="/sfide" className={css.navPill}>
                  <i className="bi bi-lightning-charge"></i>
                  Sfide
                  {challengeCount > 0 && (
                    <span className="badge rounded-pill text-bg-danger">{challengeCount}</span>
                  )}
                </NavLink>

                <NavLink to="/crea-il-tuo-evento" className={css.navPill}>
                  <i className="bi bi-stars"></i>
                  Crea il tuo evento
                </NavLink>

                <div className="dropdown">
                  <a
                    className={`${css.profileBtn} dropdown-toggle`}
                    href="#"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <UserAvatar url={user?.avatar_url} name={user?.nickname || user?.name} size={26} />
                    {user?.name || user?.nickname || "Profilo"}
                    <UserBadge badges={user?.badges} />
                  </a>

                  <ul className={`dropdown-menu dropdown-menu-end shadow-sm border-0 ${css.profileMenu}`}>
                    <li>
                      <Link className="dropdown-item" to="/profilo">
                        <i className="bi bi-person-lines-fill me-2"></i>
                        Il mio profilo
                      </Link>
                    </li>

                    <li>
                      <Link className="dropdown-item" to="/avatar">
                        <i className="bi bi-person-badge me-2"></i>
                        Il mio avatar
                      </Link>
                    </li>

                    <li>
                      <Link className="dropdown-item" to="/avatar?vista=negozio">
                        <i className="bi bi-shop me-2"></i>
                        Negozio
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

                <NavLink to="/crea-il-tuo-evento" className={css.navPill}>
                  <i className="bi bi-stars"></i>
                  Crea il tuo evento
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
