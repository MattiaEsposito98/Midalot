import { Outlet } from "react-router-dom"
import PrivateNavbar from "../components/private/PrivateNavbar"

function PrivateLayout() {
  return (
    <div className="min-vh-100">
      <PrivateNavbar />
      <main className="container py-4">
        <Outlet />
      </main>
    </div>
  )
}

export default PrivateLayout
