@extends('layouts.admin')

@section('title', 'Sala Midalario')
@section('kicker', 'Il Midalario')
@section('page-title', 'Sala d\'attesa - ' . $quiz->title)

@php
    $statusLabels = [
        'open' => ['Iscrizioni aperte', 'bg-success'],
        'closed' => ['Iscrizioni chiuse', 'bg-warning text-dark'],
        'running' => ['In corso', 'bg-primary'],
        'finished' => ['Terminato', 'bg-secondary'],
    ];
    [$label, $badgeClass] = $statusLabels[$quiz->midalario_status] ?? ['-', 'bg-secondary'];
@endphp

@section('content')
    @if (in_array($quiz->midalario_status, ['open', 'closed', 'running']))
        <meta http-equiv="refresh" content="3">
    @endif

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">
                    {{ $quiz->title }}
                    <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                </h2>
                <p class="admin-muted mb-0">
                    {{ $participants->count() }} partecipanti in sala &middot; {{ $totalQuestions }} domande
                    @if ($quiz->midalario_status === 'running' && $currentQuestionIndex !== null)
                        &middot; Domanda {{ $currentQuestionIndex + 1 }} di {{ $totalQuestions }} (ogni partecipante ha un ordine diverso)
                    @endif
                </p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.midalario.edit', $quiz) }}" class="btn btn-outline-secondary btn-sm">
                    Modifica quiz
                </a>
                <a href="{{ route('admin.quizzes.leaderboard', $quiz) }}" class="btn btn-outline-warning btn-sm">
                    Classifica
                </a>
            </div>
        </div>

        <div class="admin-card-body d-flex flex-wrap gap-2">
            @if ($quiz->midalario_status === 'open')
                <form action="{{ route('admin.midalario.close', $quiz) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-warning" onclick="return confirm('Chiudere le iscrizioni? Gli utenti non potranno piu\' partecipare.')">
                        <i class="bi bi-door-closed"></i>
                        Chiudi iscrizioni
                    </button>
                </form>
            @elseif ($quiz->midalario_status === 'closed')
                <form action="{{ route('admin.midalario.reopen', $quiz) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-secondary">
                        <i class="bi bi-door-open"></i>
                        Riapri iscrizioni
                    </button>
                </form>
                <form action="{{ route('admin.midalario.start', $quiz) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-primary" onclick="return confirm('Avviare il quiz per tutti i {{ $participants->count() }} partecipanti in sala?')">
                        <i class="bi bi-play-fill"></i>
                        Avvia quiz per tutti
                    </button>
                </form>
            @elseif ($quiz->midalario_status === 'running')
                <div class="alert alert-primary mb-0">
                    Il quiz e' in corso. Questa pagina si aggiorna automaticamente ogni 4 secondi.
                </div>
            @elseif ($quiz->midalario_status === 'finished')
                <div class="alert alert-secondary mb-0">
                    Il quiz e' terminato per tutti i partecipanti. Consulta la classifica per i risultati.
                </div>
            @endif
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-section-title">Partecipanti</h2>
        </div>

        @if ($participants->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                    <p class="mb-0">Nessun utente si e' ancora iscritto.</p>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Utente</th>
                            <th>Iscritto alle</th>
                            @if ($quiz->midalario_status === 'running')
                                <th>Domanda attuale</th>
                            @endif
                            <th>Risposte date</th>
                            <th>Stato</th>
                            @if ($quiz->midalario_status === 'finished')
                                <th>Punteggio</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            <tr>
                                <td class="fw-bold">{{ $participant['nickname'] }}</td>
                                <td>{{ optional($participant['joined_at'])->format('d/m/Y H:i') }}</td>
                                @if ($quiz->midalario_status === 'running')
                                    <td>
                                        @if ($participant['completed'])
                                            -
                                        @elseif ($participant['has_answered_current'])
                                            <span class="badge bg-success">Ha risposto</span>
                                        @else
                                            <span class="badge bg-warning text-dark">In attesa</span>
                                        @endif
                                    </td>
                                @endif
                                <td>{{ $participant['answered_count'] }} / {{ $totalQuestions }}</td>
                                <td>
                                    <span class="badge {{ $participant['completed'] ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $participant['completed'] ? 'Completato' : 'In corso' }}
                                    </span>
                                </td>
                                @if ($quiz->midalario_status === 'finished')
                                    <td>{{ $participant['score'] !== null ? number_format($participant['score'] / 100, 2, ',', '.') : '-' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
