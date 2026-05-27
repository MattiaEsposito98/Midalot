@extends('layouts.admin')

@section('title', 'Quiz')
@section('kicker', 'Gestione contenuti')
@section('page-title', 'Quiz')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Elenco quiz</h2>
                <p class="admin-muted mb-0">Crea, modifica, assegna utenti e consulta le classifiche.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
                <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i>
                    Crea quiz
                </a>
            </div>
        </div>

        @if ($quizzes->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-ui-checks-grid fs-1 d-block mb-2"></i>
                    <p class="mb-3">Nessun quiz creato.</p>
                    <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary">Crea il primo quiz</a>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Stato</th>
                            <th>Domande</th>
                            <th>Utenti</th>
                            <th>Tentativi</th>
                            <th>Creato il</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quizzes as $quiz)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $quiz->title }}</div>
                                    <div class="small admin-muted">{{ \Illuminate\Support\Str::limit($quiz->description, 90) ?: 'Nessuna descrizione' }}</div>
                                </td>
                                <td>
                                    @if ($quiz->is_active)
                                        <span class="badge bg-success">Attivo</span>
                                    @else
                                        <span class="badge bg-secondary">Non attivo</span>
                                    @endif
                                </td>
                                <td>{{ $quiz->questions_count }}</td>
                                <td>{{ $quiz->users_count }}</td>
                                <td>{{ $quiz->attempts_count }}</td>
                                <td>{{ $quiz->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-question-square"></i>
                                            Domande
                                        </a>
                                        <a href="{{ route('admin.quizzes.edit', $quiz->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                            Modifica
                                        </a>
                                        <a href="{{ route('admin.quizzes.users', $quiz->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-person-plus"></i>
                                            Utenti
                                        </a>
                                        <a href="{{ route('admin.quizzes.leaderboard', $quiz->id) }}" class="btn btn-sm btn-dark">
                                            <i class="bi bi-trophy"></i>
                                            Classifica
                                        </a>
                                        <form action="{{ route('admin.quizzes.destroy', $quiz->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Sei sicuro di voler eliminare questo quiz?')">
                                                <i class="bi bi-trash"></i>
                                                Elimina
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
