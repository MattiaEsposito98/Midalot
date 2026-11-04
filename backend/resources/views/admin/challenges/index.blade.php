@extends('layouts.admin')

@section('title', 'Sfide')
@section('kicker', 'Giochi')
@section('page-title', 'Sfide tra utenti')

@section('content')
    @php
        $statusConfig = [
            'open' => ['label' => 'In corso', 'class' => 'bg-warning text-dark'],
            'completed' => ['label' => 'Conclusa', 'class' => 'bg-success'],
            'expired' => ['label' => 'Scaduta', 'class' => 'bg-secondary'],
            'declined' => ['label' => 'Rifiutata', 'class' => 'bg-danger'],
            'cancelled' => ['label' => 'Annullata', 'class' => 'bg-dark'],
        ];
        $participantConfig = [
            'invited' => ['label' => 'invitato', 'class' => 'bg-light text-dark border'],
            'accepted' => ['label' => 'in gioco', 'class' => 'bg-info text-dark'],
            'declined' => ['label' => 'rifiutato', 'class' => 'bg-danger'],
            'missed' => ['label' => 'assente', 'class' => 'bg-secondary'],
        ];
        $s = $stats;
    @endphp

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Panoramica</h2>
        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                <i class="bi bi-lightning-charge-fill"></i>
                <span>Sfide totali</span>
                <strong>{{ $s['total'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-calendar-day"></i>
                <span>Create oggi</span>
                <strong>{{ $s['today'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-calendar-week"></i>
                <span>Ultimi 7 giorni</span>
                <strong>{{ $s['last_7_days'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-hourglass-split"></i>
                <span>In corso</span>
                <strong>{{ $s['by_status']['open'] ?? 0 }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-check2-circle"></i>
                <span>Concluse</span>
                <strong>{{ $s['by_status']['completed'] ?? 0 }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-x-circle"></i>
                <span>Scadute / rifiutate / annullate</span>
                <strong>{{ ($s['by_status']['expired'] ?? 0) + ($s['by_status']['declined'] ?? 0) + ($s['by_status']['cancelled'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Inviti e partecipazione</h2>
        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                <i class="bi bi-envelope"></i>
                <span>Inviti inviati</span>
                <strong>{{ $s['invites_total'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-controller"></i>
                <span>Invitati che hanno giocato</span>
                <strong>{{ $s['invites_played'] }}@if ($s['play_rate'] !== null) <small class="text-muted">({{ $s['play_rate'] }}%)</small>@endif</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-hand-thumbs-down"></i>
                <span>Inviti rifiutati</span>
                <strong>{{ $s['invites_by_status']['declined'] ?? 0 }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-person-x"></i>
                <span>Invitati assenti</span>
                <strong>{{ $s['invites_by_status']['missed'] ?? 0 }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-hourglass"></i>
                <span>Inviti in attesa</span>
                <strong>{{ $s['invites_by_status']['invited'] ?? 0 }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-bell-slash"></i>
                <span>Utenti che non vogliono sfide</span>
                <strong>{{ $s['opted_out'] }}</strong>
            </div>
        </div>
    </div>

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Modalità e giocatori</h2>
        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                <i class="bi bi-clock-history"></i>
                <span>Differite</span>
                <strong>{{ $s['by_mode']['async'] ?? 0 }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-broadcast"></i>
                <span>Live</span>
                <strong>{{ $s['by_mode']['live'] ?? 0 }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-people"></i>
                <span>Sfide di gruppo (3+)</span>
                <strong>{{ $s['group_challenges'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-person-lines-fill"></i>
                <span>Giocatori per sfida (media)</span>
                <strong>{{ $s['avg_participants'] ?? '-' }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-megaphone"></i>
                <span>Utenti che hanno lanciato sfide</span>
                <strong>{{ $s['unique_challengers'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-stars"></i>
                <span>XP assegnati dalle sfide</span>
                <strong>{{ $s['xp_total'] }}</strong>
            </div>
        </div>
    </div>

    @if ($s['top_challengers']->isNotEmpty())
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <section class="admin-card h-100">
                    <div class="admin-card-header">
                        <h2 class="admin-section-title">Chi lancia più sfide</h2>
                    </div>
                    <div class="admin-card-body">
                        <ol class="mb-0 ps-3">
                            @foreach ($s['top_challengers'] as $row)
                                <li>
                                    <a href="{{ route('admin.users.show', $row->id) }}" class="text-decoration-none">{{ $row->nickname }}</a>
                                    <span class="admin-muted">· {{ $row->total }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </section>
            </div>
            <div class="col-md-6">
                <section class="admin-card h-100">
                    <div class="admin-card-header">
                        <h2 class="admin-section-title">Chi vince di più</h2>
                    </div>
                    <div class="admin-card-body">
                        @if ($s['top_winners']->isEmpty())
                            <p class="admin-muted mb-0">Ancora nessuna sfida conclusa con un vincitore.</p>
                        @else
                            <ol class="mb-0 ps-3">
                                @foreach ($s['top_winners'] as $row)
                                    <li>
                                        <a href="{{ route('admin.users.show', $row->id) }}" class="text-decoration-none">{{ $row->nickname }}</a>
                                        <span class="admin-muted">· {{ $row->total }} vittorie</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    @endif

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Elenco sfide</h2>
                <p class="admin-muted mb-0">Chi ha lanciato la sfida, chi è stato invitato e come sta andando ognuno.</p>
            </div>
        </div>

        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.challenges.index') }}" class="row g-2 align-items-center mb-3">
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all">Tutti gli stati</option>
                        @foreach ($statusConfig as $key => $config)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $config['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="mode" class="form-select" onchange="this.form.submit()">
                        <option value="all">Tutte le modalità</option>
                        <option value="async" @selected($mode === 'async')>Differita</option>
                        <option value="live" @selected($mode === 'live')>Live</option>
                    </select>
                </div>
                <div class="col-auto flex-grow-1">
                    <input type="search" name="search" value="{{ $search }}" class="form-control"
                        placeholder="Cerca per nickname o nome di un partecipante...">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Cerca</button>
                </div>
                @if ($search || $status !== 'all' || $mode !== 'all')
                    <div class="col-auto">
                        <a href="{{ route('admin.challenges.index') }}" class="btn btn-outline-secondary">Azzera</a>
                    </div>
                @endif
            </form>
        </div>

        @if ($challenges->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-lightning-charge fs-1 d-block mb-2"></i>
                    <p class="mb-0">Nessuna sfida trovata.</p>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Creata il</th>
                            <th>Modalità</th>
                            <th>Stato</th>
                            <th>Lanciata da</th>
                            <th>Invitati</th>
                            <th>Categorie</th>
                            <th>Vincitore</th>
                            <th>Scadenza</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($challenges as $challenge)
                            @php
                                $cfg = $statusConfig[$challenge->status] ?? ['label' => $challenge->status, 'class' => 'bg-secondary'];
                                $overdue = $challenge->status === 'open' && $challenge->live_started_at === null && $challenge->expires_at->isPast();
                                $invitees = $challenge->participants->where('user_id', '!=', $challenge->challenger_id);
                                $winner = $challenge->participants->firstWhere('user_id', $challenge->winner_id);
                                $cats = empty($challenge->category_ids)
                                    ? 'Tutte'
                                    : collect($challenge->category_ids)->map(fn ($id) => $categoryNames[$id] ?? '?')->implode(', ');
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $challenge->id }}</td>
                                <td>{{ $challenge->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if ($challenge->mode === 'live')
                                        <span class="badge bg-danger">LIVE</span>
                                    @else
                                        <span class="badge bg-light text-dark border">Differita</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $cfg['class'] }}">{{ $cfg['label'] }}</span>
                                    @if ($overdue)
                                        <div class="small admin-muted">scaduta, da chiudere</div>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.show', $challenge->challenger_id) }}" class="text-decoration-none fw-bold">
                                        {{ $challenge->challenger->nickname }}
                                    </a>
                                </td>
                                <td>
                                    @foreach ($invitees as $p)
                                        @php
                                            $pc = $participantConfig[$p->status] ?? ['label' => $p->status, 'class' => 'bg-secondary'];
                                            $done = $challenge->attempts->firstWhere('user_id', $p->user_id)?->completed;
                                        @endphp
                                        <div class="small">
                                            <a href="{{ route('admin.users.show', $p->user_id) }}" class="text-decoration-none">{{ $p->user->nickname }}</a>
                                            @if ($done)
                                                <span class="badge bg-success">ha giocato</span>
                                            @else
                                                <span class="badge {{ $pc['class'] }}">{{ $pc['label'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td class="small">{{ $cats }}</td>
                                <td>
                                    @if ($challenge->status === 'completed')
                                        {{ $winner?->user->nickname ?? 'Pareggio' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($challenge->mode === 'live' && $challenge->live_started_at)
                                        partita {{ $challenge->live_started_at->format('d/m H:i') }}
                                    @else
                                        {{ $challenge->expires_at->format('d/m/Y H:i') }}
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.challenges.show', $challenge) }}" class="btn btn-sm btn-outline-primary">Dettagli</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="admin-card-body">
                {{ $challenges->links() }}
            </div>
        @endif
    </section>
@endsection
