import { Routes, Route, Navigate } from "react-router-dom"

import PublicLayout from "./layouts/PublicLayout"
import PrivateLayout from "./layouts/PrivateLayout"
import TrainingLayout from "./layouts/TrainingLayout"
import ProtectedRoute from "./routes/ProtectedRoute"

import Home from "./pages/Home/Home"
import Login from "./pages/Login/Login"
import Register from "./pages/Register/Register"
import QuizOneShot from "./pages/QuizOneShot/QuizOneShot"
import Quiz from "./pages/Quiz/Quiz"
import QuizReview from "./pages/QuizReview/QuizReview"
import Profilo from "./pages/Profilo/Profilo"
import ForgotPassword from "./pages/ForgotPassword/ForgotPassword"
import ResetPassword from "./pages/ResetPassword/ResetPassword"
import Storico from "./pages/Storico/Storico"
import CambiaPassword from "./pages/CambiaPassword/CambiaPassword"
import Leaderboard from "./pages/Leaderboard/Leaderboard"
import Training from "./pages/Training/Training"
import TrainingPlay from "./pages/Training/TrainingPlay"
import TrainingLeaderboard from "./pages/Training/TrainingLeaderboard"
import LegalPage from "./pages/Legal/LegalPage"
import Regolamento from "./pages/Regolamento/Regolamento"
import ChiSiamo from "./pages/ChiSiamo/ChiSiamo"
import Classifiche from "./pages/Classifiche/Classifiche"
import Midalario from "./pages/Midalario/Midalario"
import MidalarioRoom from "./pages/Midalario/MidalarioRoom"
import MinigiochiList from "./pages/Minigiochi/MinigiochiList"
import TastieraRotta from "./pages/Minigiochi/TastieraRotta"
import MinigiocoReview from "./pages/Minigiochi/MinigiocoReview"

function App() {
  return (
    <Routes>
      {/* AREA PUBBLICA */}
      <Route element={<PublicLayout />}>
        <Route path="/" element={<Home />} />
        <Route path="/chi-siamo" element={<ChiSiamo />} />
        <Route path="/classifiche" element={<Classifiche />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route path="/privacy" element={<LegalPage type="privacy" />} />
        <Route path="/termini" element={<LegalPage type="terms" />} />
        <Route path="/cookie" element={<LegalPage type="cookies" />} />
        <Route path="/regolamento" element={<Regolamento />} />
      </Route>

      {/* TRAINING PUBBLICO / UTENTI */}
      <Route element={<TrainingLayout />}>
        <Route path="/training" element={<Training />} />
        <Route path="/training/:categorySlug" element={<Training />} />
        <Route path="/training/play/:id" element={<TrainingPlay />} />
        <Route path="/training/play/:id/leaderboard" element={<TrainingLeaderboard />} />
      </Route>

      {/* AREA PRIVATA */}
      <Route
        element={
          <ProtectedRoute>
            <PrivateLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/quiz-one-shot" element={<QuizOneShot />} />
        <Route path="/dashboard" element={<Navigate to="/quiz-one-shot" replace />} />
        <Route path="/quiz/:id" element={<Quiz />} />
        <Route path="/storico" element={<Storico />} />
        <Route path="/profilo" element={<Profilo />} />
        <Route path="/cambia-password" element={<CambiaPassword />} />
        <Route path="/quiz/:id/leaderboard" element={<Leaderboard />} />
        <Route path="/quiz/:id/review" element={<QuizReview />} />
        <Route path="/midalario" element={<Midalario />} />
        <Route path="/midalario/:id" element={<MidalarioRoom />} />
        <Route path="/midalario/:id/leaderboard" element={<Leaderboard kind="midalario" />} />
        <Route path="/midalario/:id/review" element={<QuizReview kind="midalario" />} />
        <Route path="/minigiochi" element={<MinigiochiList />} />
        <Route path="/minigiochi/:id" element={<TastieraRotta />} />
        <Route path="/minigiochi/:id/leaderboard" element={<Leaderboard kind="minigioco" />} />
        <Route path="/minigiochi/:id/review" element={<MinigiocoReview />} />
      </Route>
    </Routes>
  )
}

export default App
