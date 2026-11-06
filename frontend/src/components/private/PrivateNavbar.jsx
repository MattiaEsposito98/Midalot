import { Link, NavLink, useNavigate } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import css from "./PrivateNavbar.module.css"
import XpBar from "../XpBar/XpBar"
import UserAvatar from "../UserAvatar/UserAvatar"
import { useChallengeCount } from "../../hooks/useChallengeCount"

function PrivateNavbar() {
  const { logout, user } = useAuth()
  const navigate = useNavigate()
  const challengeCount = useChallengeCount(!!user)

  const handleLogout = () => {
    logout()
    navigate("/")
  }

  return (
    <nav className={`navbar navbar-expand-xl navbar-light border-bottom shadow-sm ${css.navbar}`}>
      <div className="container-fluid px-3 px-xl-4">
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

        <div className="w-100 d-xl-none">
          <XpBar variant="navbar" />
        </div>

        <div className="collapse navbar-collapse" id="privateNavbar">
          <div className="d-none d-xl-flex flex-grow-1 justify-content-center">
            <XpBar variant="navbar" />
          </div>

          <ul className="navbar-nav align-items-xl-center gap-xl-2">
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

            <li className="nav-item">
              <NavLink className={({ isActive }) => `${css.navLink} ${isActive ? css.activeLink : ""}`} to="/minigiochi">
                <i className="bi bi-joystick"></i>
                Minigiochi
              </NavLink>
            </li>

            <li className="nav-item">
              <NavLink className={({ isActive }) => `${css.navLink} ${isActive ? css.activeLink : ""}`} to="/sfide">
                <i className="bi bi-lightning-charge"></i>
                Sfide
                {challengeCount > 0 && (
                  <span className="badge rounded-pill text-bg-danger">{challengeCount}</span>
                )}
              </NavLink>
            </li>

            <li className="nav-item">
              <NavLink className={({ isActive }) => `${css.navLink} ${isActive ? css.activeLink : ""}`} to="/crea-il-tuo-evento">
                <i className="bi bi-stars"></i>
                Crea il tuo evento
              </NavLink>
            </li>

            <li className="nav-item dropdown ms-xl-3">
              <a
                className={`${css.profileBtn} dropdown-toggle`}
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <UserAvatar url={user?.avatar_url} name={user?.nickname || user?.name} size={26} />
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
            </li>
          </ul>
        </div>
      </div>
    </nav>
  )
}

export default PrivateNavbar
