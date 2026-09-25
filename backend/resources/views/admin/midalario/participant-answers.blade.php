@extends('layouts.admin')

@section('title', 'Risposte partecipante')
@section('kicker', 'Il Midalario')
@section('activeNav', 'midalario')
@section('page-title', 'Risposte di ' . ($user->nickname ?? $user->email))

@section('content')
    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $user->nickname ?? $user->email }}</h2>
                <p class="admin-muted mb-0">
                    {{ $quiz->title }}
                    @if ($attempt?->completed)
                        &middot; Punteggio totale: <strong>{{ number_format($attempt->score / 100, 2, ',', '.') }}</strong>
                    @endif
                </p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.midalario.monitor', $quiz) }}" class="btn btn-outline-secondary btn-sm">
                    Torna alla sala
                </a>
            </div>
        </div>

        @if (! $attempt)
            <div class="admin-empty">
                <div>
                    <i class="bi bi-hourglass-split fs-1 d-block mb-2"></i>
                    <p class="mb-0">Questo utente non ha mai avuto un tentativo per questo quiz.</p>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Domanda</th>
                            <th>Risposta data</th>
                            <th>Risposta corretta</th>
                            <th>Esito</th>
                            <th>Tempo</th>
                            <th>Punti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row['question_text'] }}</td>
                                <td>{{ $row['given_answer_text'] ?? '—' }}</td>
                                <td>{{ $row['correct_answer_text'] ?? '—' }}</td>
                                <td>
                                    @if ($row['is_correct'])
                                        <span class="badge bg-success">Corretta</span>
                                    @elseif ($row['is_timeout'])
                                        <span class="badge bg-secondary">Tempo scaduto</span>
                                    @else
                                        <span class="badge bg-danger">Sbagliata</span>
                                    @endif
                                </td>
                                <td>{{ $row['time_taken'] !== null ? number_format($row['time_taken'] / 1000, 2, ',', '.') . 's' : '—' }}</td>
                                <td>{{ number_format($row['score'] / 100, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
