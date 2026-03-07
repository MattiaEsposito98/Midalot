import Navbar from "./public/PublicNavbar"
import Footer from "./public/Footer"
import { Outlet } from "react-router-dom"

function Layout() {

  return (
    <>
      <Navbar />

      <div className="container mt-4">
        <Outlet />
      </div>

      <Footer />
    </>
  )

}

export default Layout