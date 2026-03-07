import { Link } from "react-router-dom"

function Home() {
  return (
    <section className="py-5">
      <div className="container">
        <div className="row align-items-center g-4">
          <div className="col-md-6">
            <h1 className="display-5 fw-bold mb-3">
              Benvenuto nella tua piattaforma Quiz
            </h1>
            <p className="lead text-muted mb-4">
              Accedi, completa i quiz assegnati e gestisci il tuo profilo in modo semplice e veloce.
            </p>

            <div className="d-flex gap-2">
              <Link to="/login" className="btn btn-primary btn-lg">
                Accedi
              </Link>
              <Link to="/register" className="btn btn-outline-primary btn-lg">
                Registrati
              </Link>
            </div>
          </div>

          <div className="col-md-6 text-center">
            <img
              src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1200&auto=format&fit=crop"
              alt="Quiz platform"
              className="img-fluid rounded shadow"
            />
          </div>
        </div>
      </div>
    </section>
  )
}

export default Home