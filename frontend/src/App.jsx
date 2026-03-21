import { Routes, Route } from "react-router-dom"

import PublicLayout from "./layouts/PublicLayout"
import PrivateLayout from "./layouts/PrivateLayout"
import ProtectedRoute from "./routes/ProtectedRoute"

import Home from "./pages/Home"
import Login from "./pages/Login/Login"
import Register from "./pages/Register/Register"
import Dashboard from "./pages/Dashboard/Dashboard"
import Quiz from "./pages/Quiz/Quiz"
import Profilo from "./pages/Profilo"
import ForgotPassword from "./pages/ForgotPassword/ForgotPassword"

function App() {

  return (

    <Routes>

      {/* AREA PUBBLICA */}
      <Route element={<PublicLayout />}>

        <Route path="/" element={<Home />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />

      </Route>

      {/* AREA PRIVATA */}
      <Route
        element={
          <ProtectedRoute>
            <PrivateLayout />
          </ProtectedRoute>
        }
      >

        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/quiz/:id" element={<Quiz />} />
        <Route path="/profilo" element={<Profilo />} />

      </Route>

    </Routes>

  )

}

export default App