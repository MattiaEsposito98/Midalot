import { useEffect } from "react"

const DEFAULT_TITLE = "Midalot | Quiz, training e classifiche"
const DEFAULT_DESCRIPTION =
  "Midalot e' la piattaforma quiz per allenarti, completare sfide assegnate e seguire i tuoi risultati con classifiche e training per categoria."

function setMetaDescription(content) {
  let tag = document.querySelector('meta[name="description"]')
  if (!tag) {
    tag = document.createElement("meta")
    tag.setAttribute("name", "description")
    document.head.appendChild(tag)
  }
  tag.setAttribute("content", content)
}

export function useSEO({ title, description, jsonLd } = {}) {
  // Serializzato, cosi' l'effetto non riparte a ogni rendering con un oggetto nuovo.
  const jsonLdText = jsonLd ? JSON.stringify(jsonLd) : ""

  useEffect(() => {
    document.title = title || DEFAULT_TITLE
    setMetaDescription(description || DEFAULT_DESCRIPTION)

    let script = null
    if (jsonLdText) {
      script = document.createElement("script")
      script.type = "application/ld+json"
      script.dataset.seo = "page"
      script.text = jsonLdText
      document.head.appendChild(script)
    }

    return () => {
      document.title = DEFAULT_TITLE
      setMetaDescription(DEFAULT_DESCRIPTION)
      script?.remove()
    }
  }, [title, description, jsonLdText])
}
