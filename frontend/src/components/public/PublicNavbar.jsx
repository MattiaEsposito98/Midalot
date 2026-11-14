import { NavLink, Link, useNavigate } from "react-router-dom"
import { useAuth } from "../../context/useAuth"
import css from "./PublicNavbarFooter.module.css"
import UserBadge from "../UserBadge/UserBadge"
import XpBar from "../XpBar/XpBar"
import UserAvatar from "../UserAvatar/UserAvatar"
import { useChallengeCount } from "../../hooks/useChallengeCount"
import NavMenu from "../NavMenu/NavMenu"
import { GAME_ITEMS, AVATAR_ITEMS, PROFILE_ITEMS } from "../NavMenu/menuItems"

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
                <NavMenu label="Gioca" icon="bi-controller" items={GAME_ITEMS} triggerClass={css.navPill} activeClass={css.navPillActive} />

                <NavLink to="/sfide" className={css.navPill}>
                  <i className="bi bi-lightning-charge"></i>
                  Sfide
                  {challengeCount > 0 && (
                    <span className="badge rounded-pill text-bg-danger">{challengeCount}</span>
                  )}
                </NavLink>

                <NavMenu label="Avatar" icon="bi-person-badge" items={AVATAR_ITEMS} triggerClass={css.navPill} activeClass={css.navPillActive} />

                <NavLink to="/crea-il-tuo-evento" className={css.navPill}>
                  <i className="bi bi-stars"></i>
                  Crea il tuo evento
                </NavLink>

                <NavMenu
                  items={PROFILE_ITEMS}
                  triggerClass={css.profileBtn}
                  staticOnMobile={false}
                  alignEnd
                  triggerContent={
                    <>
                      <UserAvatar url={user?.avatar_url} frame={user?.avatar_frame} name={user?.nickname || user?.name} size={26} />
                      {user?.name || user?.nickname || "Profilo"}
                      <UserBadge badges={user?.badges} />
                    </>
                  }
                  footerItem={{ icon: "bi-box-arrow-right", title: "Logout", onClick: handleLogout, danger: true }}
                />
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
