import { Routes, Route } from "react-router-dom"

import PublicLayout from "./layouts/PublicLayout"
import PrivateLayout from "./layouts/PrivateLayout"
import ProtectedRoute from "./routes/ProtectedRoute"

import Home from "./pages/Home"
import Login from "./pages/Login/Login"
import Register from "./pages/Register/Register"
import Dashboard from "./pages/Dashboard/Dashboard"
import Quiz from "./pages/Quiz/Quiz"
import Profilo from "./pages/Profilo/Profilo"
import ForgotPassword from "./pages/ForgotPassword/ForgotPassword"
import ResetPassword from "./pages/ResetPassword/ResetPassword"
import Storico from "./pages/Storico/Storico"
import CambiaPassword from "./pages/CambiaPassword/CambiaPassword"
import Leaderboard from "./pages/Leaderboard/Leaderboard"

function App() {
  return (
    <Routes>
      {/* AREA PUBBLICA */}
      <Route element={<PublicLayout />}>
        <Route path="/" element={<Home />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/reset-password" element={<ResetPassword />} />
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
        <Route path="/storico" element={<Storico />} />
        <Route path="/profilo" element={<Profilo />} />
        <Route path="/cambia-password" element={<CambiaPassword />} />
        <Route path="/quiz/:id/leaderboard" element={<Leaderboard />} />
      </Route>
    </Routes>
  )
}

export default App