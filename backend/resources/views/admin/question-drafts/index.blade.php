@extends('layouts.admin')

@section('title', 'Domande da confermare')
@section('kicker', 'Controllo contenuti')
@section('page-title', 'Domande da confermare')

@section('content')
    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Bozze generate dall'AI</h2>
                <p class="admin-muted mb-0">
                    Queste domande non sono visibili agli utenti. Controllale: puoi attivarle, modificarle o eliminarle.
                    Le risposte corrette sono segnate con la spunta verde.
                </p>
            </div>
            <span class="badge text-bg-warning fs-6">{{ $total }} {{ $total === 1 ? 'bozza' : 'bozze' }}</span>
        </div>
    </section>

    @if ($total === 0)
        <section class="admin-card">
            <div class="admin-empty">
                <div>
                    <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
                    <p class="mb-0">Nessuna domanda da confermare.</p>
                </div>
            </div>
        </section>
    @endif

    @foreach ($quizzes as $quiz)
        @php $drafts = $draftsByQuiz[$quiz->id]; @endphp
        <section class="admin-card mb-3">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-section-title">{{ $quiz->title }}</h2>
                    <p class="admin-muted mb-0">
                        {{ $quiz->trainingCategory?->name ?? ucfirst($quiz->type) }}{{ $quiz->trainingSubcategory ? ' · ' . $quiz->trainingSubcategory->name : '' }}
                        · {{ $drafts->count() }} {{ $drafts->count() === 1 ? 'bozza' : 'bozze' }}
                    </p>
                </div>
                <div class="admin-page-actions">
                    <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-list-ul"></i>
                        Tutte le domande
                    </a>
                    <form action="{{ route('admin.quizzes.questions.approve-all', $quiz->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success"
                            onclick="return confirm('Attivare tutte le {{ $drafts->count() }} bozze di questo quiz? Diventeranno subito visibili agli utenti.')">
                            <i class="bi bi-check2-all"></i>
                            Attiva tutte
                        </button>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Domanda e risposte</th>
                            <th>Timer</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($drafts as $question)
                            <tr>
                                <td>
                                    {{ $question->question_text }}
                                    <ul class="list-unstyled small mb-0 mt-1">
                                        @foreach ($question->answers as $answer)
                                            <li>
                                                <i class="bi {{ $answer->is_correct ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i>
                                                {{ $answer->answer_text }}
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if ($question->ai_note)
                                        <div class="small admin-muted mt-1"><i class="bi bi-info-circle"></i> Da verificare: {{ $question->ai_note }}</div>
                                    @endif
                                </td>
                                <td>{{ $question->time_limit_seconds }} sec</td>
                                <td>
                                    <div class="admin-actions">
                                        <form action="{{ route('admin.quizzes.questions.approve', [$quiz->id, $question->id]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-lg"></i>
                                                Attiva
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.quizzes.questions.edit', [$quiz->id, $question->id]) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                            Modifica
                                        </a>
                                        <form action="{{ route('admin.quizzes.questions.destroy', [$quiz->id, $question->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Eliminare questa domanda?')">
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
        </section>
    @endforeach
@endsection
