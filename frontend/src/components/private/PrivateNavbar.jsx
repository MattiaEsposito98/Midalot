import { Link, useNavigate } from "react-router-dom"
import { useAuth } from "../../context/AuthContext"
import css from "../public/PublicNavbarFooter.module.css"

function PrivateNavbar() {
  const { logout } = useAuth()
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
              <Link className="nav-link" to="/profilo">
                Profilo
              </Link>
            </li>
            <li className="nav-item ms-lg-3">
              <button onClick={handleLogout} className="btn btn-outline-danger btn-sm">
                Logout
              </button>
            </li>
          </ul>
        </div>
      </div>
    </nav >
  )
}

export default PrivateNavbar