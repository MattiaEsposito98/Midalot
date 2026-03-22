@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Lista Quiz</h1>
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary mb-3">
            + Crea Nuovo Quiz
        </a>

        @if ($quizzes->isEmpty())
            <p>Nessun quiz creato.</p>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titolo</th>
                        <th>Descrizione</th>
                        <th>Attivo</th>
                        <th>Creato il</th>
                        <th>Azioni</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($quizzes as $quiz)
                        <tr>
                            <td>{{ $quiz->id }}</td>
                            <td>{{ $quiz->title }}</td>
                            <td>{{ $quiz->description }}</td>
                            <td>{{ $quiz->is_active ? 'Si' : 'No' }}</td>
                            <td>{{ $quiz->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-sm btn-info">
                                    Domande
                                </a>

                                <a href="{{ route('admin.quizzes.edit', $quiz->id) }}" class="btn btn-sm btn-warning">
                                    Modifica
                                </a>

                                <a href="{{ route('admin.quizzes.users', $quiz->id) }}" class="btn btn-sm btn-secondary">
                                    Associa Utenti
                                </a>

                                <a href="{{ route('admin.quizzes.leaderboard', $quiz->id) }}" class="btn btn-sm btn-dark">
                                    Classifica
                                </a>

                                <form action="{{ route('admin.quizzes.destroy', $quiz->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Sei sicuro di voler eliminare questo quiz?')">
                                        Elimina
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
