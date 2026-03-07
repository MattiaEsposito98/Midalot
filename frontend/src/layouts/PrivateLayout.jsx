import { Outlet } from "react-router-dom"
import PrivateNavbar from "../components/private/PrivateNavbar"

function PrivateLayout() {
  return (
    <div className="min-vh-100 bg-light">
      <PrivateNavbar />
      <main className="container py-4">
        <Outlet />
      </main>
    </div>
  )
}

export default PrivateLayout