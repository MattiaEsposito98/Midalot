import { Outlet } from "react-router-dom"
import PublicNavbar from "../components/public/PublicNavbar"
import Footer from "../components/public/Footer"

function PublicLayout() {
  return (
    <div className="d-flex flex-column min-vh-100">
      <PublicNavbar />
      <main className="flex-grow-1">
        <Outlet />
      </main>
      <Footer />
    </div>
  )
}

export default PublicLayout