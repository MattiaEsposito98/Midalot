import { useEffect } from "react"

const SITE = "https://midalot.live"

// Pagine pubbliche che devono comparire su Google. Tutto il resto (area
// personale, sfide, avatar, link con token...) e' noindex.
const INDEXABLE = [/^\/$/, /^\/chi-siamo$/, /^\/crea-il-tuo-evento$/, /^\/servizi-digitali$/, /^\/classifiche$/, /^\/training(\/[^/]+)?$/, /^\/register$/, /^\/login$/, /^\/privacy$/, /^\/termini$/, /^\/cookie$/, /^\/regolamento$/]

function setLink(rel, href) {
  let tag = document.querySelector(`link[rel="${rel}"]`)
  if (!tag) {
    tag = document.createElement("link")
    tag.setAttribute("rel", rel)
    document.head.appendChild(tag)
  }
  tag.setAttribute("href", href)
}

function setMeta(name, content) {
  let tag = document.querySelector(`meta[name="${name}"]`)
  if (!tag) {
    tag = document.createElement("meta")
    tag.setAttribute("name", name)
    document.head.appendChild(tag)
  }
  tag.setAttribute("content", content)
}

/**
 * Canonical e indicizzazione per ogni pagina. Prima il canonical era sempre la
 * home (scritto in index.html), cosi' Google trattava tutte le pagine come
 * copie della home e non le indicizzava.
 */
export function useRouteSeo(pathname) {
  useEffect(() => {
    const path = pathname.length > 1 ? pathname.replace(/\/+$/, "") : pathname
    const indexable = INDEXABLE.some((re) => re.test(path))

    setLink("canonical", SITE + (path === "/" ? "/" : path))
    setMeta("robots", indexable ? "index, follow" : "noindex, nofollow")
  }, [pathname])
}
