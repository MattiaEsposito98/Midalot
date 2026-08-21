import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import styles from "./ChiSiamo.module.css"
import api from "../../service/api"
import { logError } from "../../utils/logger"

function ImageMarquee({ images, onSelect, emptyText }) {
  if (images.length === 0) {
    return <div className={styles.emptyState}>{emptyText}</div>
  }

  const shouldScroll = images.length > 2
  const track = shouldScroll ? [...images, ...images] : images

  return (
    <div className={styles.marqueeWrap}>
      <div
        className={`${styles.marqueeTrack} ${shouldScroll ? "" : styles.marqueeStatic}`}
        style={{ "--items": images.length }}
      >
        {track.map((image, index) => (
          <button
            type="button"
            key={`${image.id}-${index}`}
            className={styles.imageCard}
            onClick={() => onSelect(image)}
            aria-label={`Apri immagine di ${image.caption || "collaborazione"}`}
          >
            <img src={image.url} alt={image.caption || ""} className={styles.image} />
            {image.caption && <span className={styles.imageCaption}>{image.caption}</span>}
          </button>
        ))}
      </div>
    </div>
  )
}

function ChiSiamo() {
  const [testimonials, setTestimonials] = useState([])
  const [collabs, setCollabs] = useState([])
  const [selectedImage, setSelectedImage] = useState(null)

  useEffect(() => {
    api.get("/showcase")
      .then((res) => {
        setTestimonials(res.data.testimonials || [])
        setCollabs(res.data.collabs || [])
      })
      .catch((err) => logError(err))
  }, [])

  return (
    <section className={styles.page}>
      <div className={`container ${styles.wrap}`}>
        <Link to="/" className={styles.backLink}>
          <i className="bi bi-arrow-left"></i>
          Torna alla home
        </Link>

        <div className={styles.header}>
          <span className={styles.eyebrow}>Giveaway - Collaborazioni - Servizi digitali</span>
          <h1>Chi siamo</h1>
          <p>
            Siamo mida.lot: organizziamo giveaway, collaborazioni Instagram e servizi
            digitali. Midalot nasce proprio da questa community.
          </p>

          <div className={styles.headerActions}>
            <a
              href="https://www.instagram.com/mida.lot/"
              target="_blank"
              rel="noopener noreferrer"
              className={styles.primaryBtn}
            >
              <i className="bi bi-instagram"></i>
              Vai su Instagram
            </a>
            <a href="#servizi" className={styles.secondaryBtn}>
              Scopri i servizi
            </a>
          </div>
        </div>

        <div id="servizi" className={styles.section}>
          <span className={styles.sectionBadge}>Servizi digitali</span>
          <h2>I nostri servizi</h2>

          <div className={styles.servicesGrid}>
            <div className={styles.serviceCard}>
              <div className={styles.serviceBadge}>Siti vetrina</div>
              <h3>Realizzazione siti vetrina</h3>
              <p>Siti vetrina moderni e professionali a un ottimo prezzo, su misura per la tua attivita' o il tuo brand.</p>
              <a
                href="https://www.instagram.com/mida.lot/"
                target="_blank"
                rel="noopener noreferrer"
                className={styles.primaryBtn}
              >
                Richiedi un preventivo
              </a>
            </div>

            <div className={styles.serviceCard}>
              <div className={styles.serviceBadge}>Instagram</div>
              <h3>Analisi follower</h3>
              <p>Scopri quali account segui ma non ti ricambiano il follow, con una panoramica chiara del tuo profilo.</p>
              <a
                href="https://www.instagram.com/mida.lot/"
                target="_blank"
                rel="noopener noreferrer"
                className={styles.secondaryBtn}
              >
                Contattaci per saperne di piu'
              </a>
            </div>
          </div>
        </div>

        <div id="testimonianze" className={styles.section}>
          <span className={styles.sectionBadge}>Testimonianze</span>
          <h2>Cosa dice la community</h2>

          <ImageMarquee
            images={testimonials}
            onSelect={setSelectedImage}
            emptyText="Nessuna testimonianza disponibile al momento."
          />
        </div>

        <div id="collaborazioni" className={styles.section}>
          <span className={styles.sectionBadge}>Collaborazioni</span>
          <h2>Progetti con cui abbiamo collaborato</h2>

          <ImageMarquee
            images={collabs}
            onSelect={setSelectedImage}
            emptyText="Nessuna collaborazione disponibile al momento."
          />
        </div>
      </div>

      {selectedImage && (
        <div className={styles.lightbox} onClick={() => setSelectedImage(null)}>
          <div className={styles.lightboxContent} onClick={(e) => e.stopPropagation()}>
            <button
              type="button"
              className={styles.lightboxClose}
              onClick={() => setSelectedImage(null)}
              aria-label="Chiudi"
            >
              <i className="bi bi-x-lg"></i>
            </button>
            <img src={selectedImage.url} alt={selectedImage.caption || ""} className={styles.lightboxImage} />
          </div>
        </div>
      )}
    </section>
  )
}

export default ChiSiamo
