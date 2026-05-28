import css from "./PublicNavbarFooter.module.css"
import { Link } from "react-router-dom"

function Footer() {
  return (
    <footer className={css.footer}>
      <div className={`container ${css.footerInner}`}>
        <div className={css.footerBrand}>
          <div className={css.footerLogoWrap}>
            <Link className="navbar-brand fw-bold m-0" to="/">
              <img
                src="/Midalot.png"
                alt="logo Midalot"
                className={css.footerLogo}
              />
            </Link>

            <span className={css.footerBrandName}>
              Midalot
            </span>
          </div>

          <p className={css.footerCopy}>
            Copyright {new Date().getFullYear()} Midalot. Tutti i diritti riservati.
          </p>
        </div>

        <div className={css.footerContacts}>
          <p className={css.footerContactText}>
            Contattaci
          </p>

          <div className={css.footerLinks}>
            <a
              href="https://www.instagram.com/mida.lot/"
              target="_blank"
              rel="noopener noreferrer"
              className={css.footerLink}
            >
              <i className="bi bi-instagram"></i>
              <span>@mida.lot</span>
            </a>

            <a
              href="mailto:midalot@libero.it"
              className={css.footerLink}
            >
              <i className="bi bi-envelope"></i>
              <span>midalot@libero.it</span>
            </a>
          </div>
        </div>
      </div>
    </footer>
  )
}

export default Footer
