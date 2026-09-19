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

export function useSEO({ title, description } = {}) {
  useEffect(() => {
    document.title = title || DEFAULT_TITLE
    setMetaDescription(description || DEFAULT_DESCRIPTION)

    return () => {
      document.title = DEFAULT_TITLE
      setMetaDescription(DEFAULT_DESCRIPTION)
    }
  }, [title, description])
}
