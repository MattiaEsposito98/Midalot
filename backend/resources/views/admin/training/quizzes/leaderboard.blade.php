@extends('layouts.admin')

@section('title', 'Classifica training')
@section('kicker', 'Risultati')
@section('page-title', 'Classifica')
@section('activeNav', 'training')

@section('content')
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
            </div>
            <div class="admin-page-actions">
                <form action="{{ route('admin.training.quizzes.toggleLeaderboard', $quiz) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm {{ $quiz->leaderboard_visible ? 'btn-outline-danger' : 'btn-success' }}">
                        <i class="bi bi-eye{{ $quiz->leaderboard_visible ? '-slash' : '' }}"></i>
                        {{ $quiz->leaderboard_visible ? 'Nascondi agli utenti' : 'Mostra agli utenti' }}
                    </button>
                </form>
                <a href="{{ route('admin.training.quizzes.edit', $quiz) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil"></i>
                    Modifica training
                </a>
                <a href="{{ route('admin.training.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Torna ai training
                </a>
            </div>
        </div>

        @if ($results->isEmpty())
            <div class="admin-empty">Nessun risultato disponibile per questo training.</div>
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
