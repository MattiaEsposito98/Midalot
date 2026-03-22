import { Link, useNavigate } from "react-router-dom"
import { useAuth } from "../../context/AuthContext"
import css from "../public/PublicNavbarFooter.module.css"

function PrivateNavbar() {
  const { logout, user } = useAuth()
  const navigate = useNavigate()

  const handleLogout = () => {
    logout()
    navigate("/")
  }

  return (
    <nav className="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
      <div className="container">
        <Link className="navbar-brand fw-bold" to="/dashboard">
          <img
            src="/Midalot.png"
            alt="logo Midalot"
            className={css.logo}
          />
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
          <ul className="navbar-nav ms-auto align-items-lg-center">
            <li className="nav-item">
              <Link className="nav-link" to="/dashboard">
                Dashboard
              </Link>
            </li>

            <li className="nav-item">
              <Link className="nav-link" to="/storico">
                Storico
              </Link>
            </li>

            <li className="nav-item dropdown ms-lg-3">
              <a
                className="nav-link dropdown-toggle"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                {user?.name || user?.username || "Profilo"}
              </a>

              <ul className="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li>
                  <Link className="dropdown-item" to="/profilo">
                    Il mio profilo
                  </Link>
                </li>
                <li>
                  <Link className="dropdown-item" to="/cambia-password">
                    Cambia password
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