import { Link, useLocation } from "react-router-dom"
import css from "./NavMenu.module.css"

/**
 * Tendina della navbar (utente collegato).
 *
 * Con `staticOnMobile` (predefinito) sotto i 1200 px, dentro il menu a
 * scomparsa, non e' una tendina: il pulsante fa da titolo e le voci restano
 * visibili. Senza, resta una tendina normale (menu del nome).
 *
 * @param {string}    label          testo del pulsante (se non c'e' `triggerContent`)
 * @param {string}    icon           icona Bootstrap del pulsante
 * @param {ReactNode} triggerContent contenuto personalizzato del pulsante (es. avatar e nome)
 * @param {Array}     items          voci (vedi menuItems.js)
 * @param {object}    footerItem     voce finale separata, es. Esci: { icon, title, onClick, danger }
 * @param {string}    triggerClass   stile del pulsante, preso dalla navbar che la usa
 * @param {string}    activeClass    stile aggiunto quando si e' su una pagina del gruppo
 * @param {boolean}   alignEnd       apre la tendina allineata a destra
 */
export default function NavMenu({
  label,
  icon,
  triggerContent,
  items,
  footerItem,
  triggerClass = "",
  activeClass = "",
  staticOnMobile = true,
  alignEnd = false,
}) {
  const { pathname, search } = useLocation()
  const isActive = (item) => item.test(pathname, search)
  const active = items.some(isActive)

  return (
    <div className={`dropdown ${css.wrap} ${staticOnMobile ? css.static : ""}`}>
      <button
        type="button"
        className={`${triggerClass} ${active ? activeClass : ""} ${staticOnMobile ? css.trigger : css.triggerBare} dropdown-toggle`}
        data-bs-toggle="dropdown"
        aria-expanded="false"
      >
        {triggerContent ?? (
          <>
            <i className={`bi ${icon}`}></i>
            {label}
          </>
        )}
      </button>

      <ul className={`dropdown-menu ${alignEnd ? "dropdown-menu-end" : ""} shadow-sm border-0 ${css.menu}`}>
        {items.map((item) => (
          <li key={item.to}>
            <Link className={`dropdown-item ${css.item} ${isActive(item) ? css.itemActive : ""}`} to={item.to}>
              <span className={css.icon}>
                <i className={`bi ${item.icon}`}></i>
              </span>
              <span>
                <strong>{item.title}</strong>
                {item.text && <small>{item.text}</small>}
              </span>
            </Link>
          </li>
        ))}

        {footerItem && (
          <>
            <li>
              <hr className="dropdown-divider" />
            </li>
            <li>
              <button type="button" className={`dropdown-item ${css.item} ${footerItem.danger ? css.itemDanger : ""}`} onClick={footerItem.onClick}>
                <span className={css.icon}>
                  <i className={`bi ${footerItem.icon}`}></i>
                </span>
                <span>
                  <strong>{footerItem.title}</strong>
                </span>
              </button>
            </li>
          </>
        )}
      </ul>
    </div>
  )
}
