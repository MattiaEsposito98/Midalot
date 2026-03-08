import { useEffect, useState } from "react"
import { useAuth } from "../context/AuthContext"
import { Link } from "react-router-dom"

function Dashboard() {

  const { token } = useAuth()
  const [quizzes, setQuizzes] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {

    async function loadQuizzes() {

      try {

        const res = await fetch("http://localhost:8000/api/my-quizzes", {
          headers: {
            Authorization: `Bearer ${token}`
          }
        })

        const data = await res.json()
        console.log(data)

        setQuizzes(data.quizzes || [])

      } catch (error) {
        console.error("Errore caricamento quiz", error)
      }

      setLoading(false)
    }

    loadQuizzes()

  }, [token])

  if (loading) return <p>Caricamento quiz...</p>

  return (
    <div>

      <h1>I tuoi quiz</h1>

      {quizzes.length === 0 && (
        <p>Nessun quiz assegnato</p>
      )}

      {quizzes.map(q => (

        <div key={q.id} style={{ marginBottom: 20 }}>

          <h3>{q.title}</h3>
          <p>{q.description}</p>
          <p>{q.questions_count} domande</p>

          <Link to={`/quiz/${q.id}`}>
            Inizia quiz
          </Link>

        </div>

      ))}

    </div>
  )
}

export default Dashboard