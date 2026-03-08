import { useParams } from "react-router-dom"
import { useEffect, useState } from "react"
import { useAuth } from "../context/AuthContext"

function Quiz() {

  const { id } = useParams()
  const { token } = useAuth()

  const [quiz, setQuiz] = useState(null)

  useEffect(() => {

    async function loadQuiz() {

      try {

        const res = await fetch(`http://localhost:8000/api/quizzes/${id}`, {
          headers: {
            Authorization: `Bearer ${token}`
          }
        })

        if (!res.ok) {
          throw new Error("Errore caricamento quiz")
        }

        const data = await res.json()

        setQuiz(data.quiz)

      } catch (error) {
        console.error(error)
      }

    }

    loadQuiz()

  }, [id, token])

  if (!quiz) return <p>Caricamento quiz...</p>

  return (

    <div>

      <h1>{quiz.title}</h1>
      <p>{quiz.description}</p>

      {quiz.questions.map((q, index) => (

        <div key={q.id} style={{ marginBottom: 40 }}>

          <h3>
            Domanda {index + 1}
          </h3>

          <p>{q.question_text}</p>

          {q.answers.map(a => (

            <div key={a.id}>

              <button>
                {a.answer_text}
              </button>

            </div>

          ))}

        </div>

      ))}

    </div>

  )
}

export default Quiz