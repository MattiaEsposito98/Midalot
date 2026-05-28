import { Link, useNavigate } from "react-router-dom"
import { useAuth } from "../../context/AuthContext"
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
        <Link className={`navbar-brand fw-bold ${css.brand}`} to="/dashboard">
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
              <Link className={css.navLink} to="/dashboard">
                Dashboard
              </Link>
            </li>

            <li className="nav-item">
              <Link className={css.navLink} to="/training">
                Training
              </Link>
            </li>

            <li className="nav-item">
              <Link className={css.navLink} to="/storico">
                Storico
              </Link>
            </li>

            <li className="nav-item dropdown ms-lg-3">
              <a
                className={`${css.profileBtn} dropdown-toggle`}
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                {user?.name || user?.username || "Profilo"}
              </a>

              <ul className={`dropdown-menu dropdown-menu-end shadow-sm border-0 ${css.profileMenu}`}>
                <li>
                  <Link className="dropdown-item" to="/profilo">
                    Il mio profilo
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
