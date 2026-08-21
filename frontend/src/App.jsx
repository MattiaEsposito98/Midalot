import { Routes, Route } from "react-router-dom"

import PublicLayout from "./layouts/PublicLayout"
import PrivateLayout from "./layouts/PrivateLayout"
import TrainingLayout from "./layouts/TrainingLayout"
import ProtectedRoute from "./routes/ProtectedRoute"

import Home from "./pages/Home/Home"
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
import Training from "./pages/Training/Training"
import TrainingPlay from "./pages/Training/TrainingPlay"
import LegalPage from "./pages/Legal/LegalPage"
import Regolamento from "./pages/Regolamento/Regolamento"
import ChiSiamo from "./pages/ChiSiamo/ChiSiamo"

function App() {
  return (
    <Routes>
      {/* AREA PUBBLICA */}
      <Route element={<PublicLayout />}>
        <Route path="/" element={<Home />} />
        <Route path="/chi-siamo" element={<ChiSiamo />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route path="/privacy" element={<LegalPage type="privacy" />} />
        <Route path="/termini" element={<LegalPage type="terms" />} />
        <Route path="/cookie" element={<LegalPage type="cookies" />} />
      </Route>

      {/* TRAINING PUBBLICO / UTENTI */}
      <Route element={<TrainingLayout />}>
        <Route path="/training" element={<Training />} />
        <Route path="/training/:categorySlug" element={<Training />} />
        <Route path="/training/play/:id" element={<TrainingPlay />} />
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
        <Route path="/regolamento" element={<Regolamento />} />
        <Route path="/cambia-password" element={<CambiaPassword />} />
        <Route path="/quiz/:id/leaderboard" element={<Leaderboard />} />
      </Route>
    </Routes>
  )
}

export default App
