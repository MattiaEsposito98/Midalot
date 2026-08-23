@extends('layouts.admin')

@section('title', 'Classifica quiz')
@section('kicker', 'Risultati')
@section('page-title', 'Classifica')

@php
    $backRoute = match ($quiz->type) {
        'midalario' => route('admin.midalario.monitor', $quiz),
        'training' => route('admin.training.quizzes.index'),
        default => route('admin.quizzes.index'),
    };
    $backLabel = match ($quiz->type) {
        'midalario' => "Torna alla sala Midalario",
        'training' => 'Torna ai training',
        default => 'Torna ai quiz',
    };
@endphp

@section('content')
    @if ($quiz->isMidalario() && $quiz->midalario_status === 'running')
        <meta http-equiv="refresh" content="4">
    @endif

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $quiz->title }}</h2>
                <p class="admin-muted mb-2">Risultati ordinati per completamento, punteggio e tempo.</p>

                @if ($quiz->leaderboard_visible)
                    <span class="badge bg-success">
                        <i class="bi bi-eye me-1"></i>
                        La classifica e' visibile agli utenti
                    </span>
                @else
                    <span class="badge bg-secondary">
                        <i class="bi bi-eye-slash me-1"></i>
                        La classifica e' nascosta agli utenti
                    </span>
                @endif

                @if ($quiz->isMidalario() && $quiz->midalario_status === 'running')
                    <div class="alert alert-primary mt-2 mb-0 py-1 px-2 d-inline-block">
                        <i class="bi bi-arrow-repeat"></i>
                        Il quiz e' in corso. Questa pagina si aggiorna automaticamente ogni 4 secondi.
                    </div>
                @endif
            </div>
            <div class="admin-page-actions">
                <form action="{{ route('admin.quizzes.toggleLeaderboard', $quiz) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm {{ $quiz->leaderboard_visible ? 'btn-outline-danger' : 'btn-success' }}">
                        <i class="bi bi-eye{{ $quiz->leaderboard_visible ? '-slash' : '' }}"></i>
                        {{ $quiz->leaderboard_visible ? 'Nascondi agli utenti' : 'Mostra agli utenti' }}
                    </button>
                </form>
                <a href="{{ $backRoute }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    {{ $backLabel }}
                </a>
            </div>
        </div>

        @if ($results->isEmpty())
            <div class="admin-empty">Nessun risultato disponibile per questo quiz.</div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Utente</th>
                            <th>Email</th>
                            <th>Punteggio</th>
                            <th>Risposte corrette</th>
                            <th>Tempo totale</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $index => $r)
                            <tr>
                                <td><span class="badge bg-primary">{{ $index + 1 }}</span></td>
                                <td class="fw-bold">{{ $r->user->nickname ?? ($r->user->name ?? '-') }}</td>
                                <td>{{ $r->user->email ?? '-' }}</td>
                                <td><strong>{{ number_format(($r->score ?? 0) / 100, 2, ',', '.') }}</strong></td>
                                <td>{{ $r->correct_answers ?? 0 }} / {{ $r->total_questions ?? 0 }}</td>
                                <td>
                                    @if (!is_null($r->total_time))
                                        @php
                                            $ms = (int) $r->total_time;
                                            $seconds = floor($ms / 1000);
                                            $minutes = floor($seconds / 60);
                                            $remainingSeconds = $seconds % 60;
                                            $milliseconds = $ms % 1000;
                                        @endphp
                                        {{ sprintf('%d:%02d.%03d', $minutes, $remainingSeconds, $milliseconds) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($r->completed)
                                        <span class="badge bg-success">Completato</span>
                                    @else
                                        <span class="badge bg-warning text-dark">In corso</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
