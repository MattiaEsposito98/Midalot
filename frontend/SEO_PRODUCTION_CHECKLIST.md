# SEO production checklist

Prima della pubblicazione online:

- Copiare `docs/sitemap.template.xml` in `public/sitemap.xml` e sostituire `https://tuo-dominio.it` con il dominio reale.
- Aggiungere `Sitemap: https://dominio-reale/sitemap.xml` in `public/robots.txt`.
- Valutare canonical URL in `index.html` solo quando il dominio e' definitivo.
- Usare URL assoluti per `og:image` e `twitter:image` se il dominio pubblico e' noto.
- Verificare titolo e descrizione con il nome commerciale definitivo.
- Collegare Google Search Console dopo il deploy.
- Eseguire Lighthouse su home, training, login e registrazione.
