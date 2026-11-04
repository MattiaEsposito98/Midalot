@extends('layouts.admin')

@section('title', 'Sfida #'.$challenge->id)
@section('kicker', 'Sfide')
@section('page-title', 'Sfida #'.$challenge->id)

@section('content')
    @php
        $statusLabel = [
            'open' => 'In corso',
            'completed' => 'Conclusa',
            'expired' => 'Scaduta',
            'declined' => 'Rifiutata',
            'cancelled' => 'Annullata',
        ][$challenge->status] ?? $challenge->status;

        $participantLabel = [
            'invited' => 'Invitato, non ha ancora iniziato',
            'accepted' => 'Accettato',
            'declined' => 'Ha rifiutato',
            'missed' => 'Assente',
        ];

        $cats = empty($challenge->category_ids)
            ? 'Tutte le categorie'
            : collect($challenge->category_ids)->map(fn ($id) => $categoryNames[$id] ?? '?')->implode(', ');

        $rankByUser = $ranked->mapWithKeys(fn ($row) => [$row['attempt']->user_id => $row['rank']]);
    @endphp

    <div class="mb-3">
        <a href="{{ route('admin.challenges.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Tutte le sfide
        </a>
    </div>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Riepilogo</h2>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-3"><div class="small text-uppercase fw-bold admin-muted">Stato</div>{{ $statusLabel }}</div>
                <div class="col-md-3"><div class="small text-uppercase fw-bold admin-muted">Modalità</div>{{ $challenge->mode === 'live' ? 'Live' : 'Differita' }}</div>
                <div class="col-md-3"><div class="small text-uppercase fw-bold admin-muted">Creata il</div>{{ $challenge->created_at->format('d/m/Y H:i') }}</div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted">{{ $challenge->live_started_at ? 'Partita il' : 'Scadenza' }}</div>
                    {{ ($challenge->live_started_at ?? $challenge->expires_at)->format('d/m/Y H:i:s') }}
                </div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted">Lanciata da</div>
                    <a href="{{ route('admin.users.show', $challenge->challenger_id) }}" class="text-decoration-none">{{ $challenge->challenger->nickname }}</a>
                </div>
                <div class="col-md-3"><div class="small text-uppercase fw-bold admin-muted">Categorie</div>{{ $cats }}</div>
                <div class="col-md-3"><div class="small text-uppercase fw-bold admin-muted">Chiusa il</div>{{ $challenge->completed_at?->format('d/m/Y H:i') ?? '-' }}</div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted">Vincitore</div>
                    @if ($challenge->status === 'completed')
                        {{ $challenge->participants->firstWhere('user_id', $challenge->winner_id)?->user->nickname ?? 'Pareggio' }}
                    @else
                        -
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <h2 class="admin-section-title">Partecipanti</h2>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Pos.</th>
                        <th>Giocatore</th>
                        <th>Ruolo</th>
                        <th>Stato invito</th>
                        <th>Partita</th>
                        <th>Punteggio</th>
                        <th>Corrette</th>
                        <th>Tempo totale</th>
                        <th>XP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($challenge->participants as $p)
                        @php $attempt = $challenge->attempts->firstWhere('user_id', $p->user_id); @endphp
                        <tr>
                            <td>{{ $attempt?->completed ? ($rankByUser[$p->user_id] ?? '-') : '-' }}</td>
                            <td>
                                <a href="{{ route('admin.users.show', $p->user_id) }}" class="text-decoration-none fw-bold">{{ $p->user->nickname }}</a>
                                <div class="small admin-muted">{{ $p->user->email }}</div>
                            </td>
                            <td>{{ $p->user_id === $challenge->challenger_id ? 'Sfidante' : 'Invitato' }}</td>
                            <td>{{ $participantLabel[$p->status] ?? $p->status }}</td>
                            <td>
                                @if ($attempt?->completed)
                                    <span class="badge bg-success">Finita</span>
                                    <div class="small admin-muted">{{ $attempt->finished_at?->format('d/m H:i') }}</div>
                                @elseif ($attempt)
                                    <span class="badge bg-warning text-dark">In corso</span>
                                @else
                                    <span class="badge bg-secondary">Non iniziata</span>
                                @endif
                            </td>
                            <td>{{ $attempt?->completed ? number_format($attempt->score / 100, 2, ',', '.') : '-' }}</td>
                            <td>{{ $attempt?->completed ? $attempt->correct_answers.' / '.$questions->count() : '-' }}</td>
                            <td>{{ $attempt?->completed ? number_format($attempt->total_time / 1000, 1, ',', '.').' s' : '-' }}</td>
                            <td>{{ $xpByUser[$p->user_id] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Domande estratte</h2>
                <p class="admin-muted mb-0">Quante risposte corrette per ogni domanda (solo giocatori che hanno finito).</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Domanda</th>
                        <th>Categoria</th>
                        <th>Risposta corretta</th>
                        <th>Corrette</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($questions as $i => $question)
                        @php
                            $answers = $challenge->attempts->where('completed', true)
                                ->map(fn ($a) => $a->answers->firstWhere('question_id', $question->id))
                                ->filter();
                            $correct = $answers->where('is_correct', true)->count();
                            $category = $question->quiz?->trainingCategory?->name;
                            $sub = $question->quiz?->trainingSubcategory?->name;
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $question->question_text }}</td>
                            <td class="small">
                                {{ $category ?? '-' }}
                                @if ($sub && $sub !== $category)
                                    <span class="admin-muted">› {{ $sub }}</span>
                                @endif
                            </td>
                            <td class="small">{{ $question->answers->firstWhere('is_correct', true)?->answer_text ?? '-' }}</td>
                            <td>{{ $answers->count() > 0 ? $correct.' / '.$answers->count() : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
