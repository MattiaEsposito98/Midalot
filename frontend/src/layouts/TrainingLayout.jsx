import { Outlet } from "react-router-dom"
import { useAuth } from "../context/AuthContext"
import PublicNavbar from "../components/public/PublicNavbar"
import PrivateNavbar from "../components/private/PrivateNavbar"
import Footer from "../components/public/Footer"

function TrainingLayout() {
  const { token } = useAuth()

  return (
    <div className="d-flex flex-column min-vh-100 bg-light">
      {token ? <PrivateNavbar /> : <PublicNavbar />}

      <main className="flex-grow-1">
        <Outlet />
      </main>

      {!token && <Footer />}
    </div>
  )
}

export default TrainingLayout
