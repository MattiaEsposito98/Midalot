import { Link, NavLink, useNavigate } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import css from "./PrivateNavbar.module.css"
import XpBar from "../XpBar/XpBar"
import UserAvatar from "../UserAvatar/UserAvatar"
import { useChallengeCount } from "../../hooks/useChallengeCount"
import NavMenu from "../NavMenu/NavMenu"
import { GAME_ITEMS, AVATAR_ITEMS, PROFILE_ITEMS } from "../NavMenu/menuItems"

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
              <NavMenu label="Gioca" icon="bi-controller" items={GAME_ITEMS} triggerClass={css.navLink} activeClass={css.activeLink} />
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
              <NavMenu label="Avatar" icon="bi-person-badge" items={AVATAR_ITEMS} triggerClass={css.navLink} activeClass={css.activeLink} />
            </li>

            <li className="nav-item">
              <NavLink className={({ isActive }) => `${css.navLink} ${isActive ? css.activeLink : ""}`} to="/crea-il-tuo-evento">
                <i className="bi bi-stars"></i>
                Crea il tuo evento
              </NavLink>
            </li>

            <li className="nav-item ms-xl-3">
              <NavMenu
                items={PROFILE_ITEMS}
                triggerClass={css.profileBtn}
                staticOnMobile={false}
                alignEnd
                triggerContent={
                  <>
                    <UserAvatar url={user?.avatar_url} frame={user?.avatar_frame} name={user?.nickname || user?.name} size={26} />
                    {user?.name || user?.username || "Profilo"}
                  </>
                }
                footerItem={{ icon: "bi-box-arrow-right", title: "Logout", onClick: handleLogout, danger: true }}
              />
            </li>
          </ul>
        </div>
      </div>
    </nav>
  )
}

export default PrivateNavbar
