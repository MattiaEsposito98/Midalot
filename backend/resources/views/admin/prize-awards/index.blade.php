@extends('layouts.admin')

@section('title', 'Premi in monete')
@section('kicker', 'Premi e avatar')
@section('page-title', 'Premi in monete')

@section('content')
    @php
        $fmt = fn ($n) => number_format($n, 0, ',', '.');
        $monthLabel = Illuminate\Support\Str::ucfirst($targetMonth->locale('it')->translatedFormat('F Y'));
        $nextRun = $targetMonth->copy()->addMonth()->startOfMonth()->setTime(0, 10);
        $kinds = ['monthly' => 'Classifica mensile', 'midalario' => 'Midalario'];
    @endphp

    <p class="admin-muted">
        Registro dei premi in monete assegnati in automatico ai primi 5 della classifica mensile (il 1° del mese, ore 00:10)
        e di ogni Midalario (alla chiusura della partita). Qui si vede a chi sono andati, quanti e quando; la pagina è di sola lettura.
    </p>

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Riepilogo</h2>
        <div class="admin-stat-grid">
            @foreach ($kinds as $k => $name)
                <div class="admin-stat-card">
                    <i class="bi {{ $k === 'monthly' ? 'bi-calendar-month' : 'bi-broadcast' }}"></i>
                    <span>{{ $name }}: monete assegnate</span>
                    <strong>{{ $fmt($summary[$k]['coins']) }}</strong>
                </div>
                <div class="admin-stat-card">
                    <i class="bi bi-people"></i>
                    <span>{{ $name }}: premi in {{ $summary[$k]['events'] }} {{ $k === 'monthly' ? 'mesi' : 'sessioni' }}</span>
                    <strong>{{ $summary[$k]['awards'] }} <small class="text-muted">a {{ $summary[$k]['winners'] }} utenti</small></strong>
                </div>
            @endforeach
        </div>
    </div>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Scale dei premi</h2>
                <p class="admin-muted mb-0">
                    Pari merito: stessa posizione e stesso premio, il successivo salta (due primi → il prossimo è 3°). 0 punti = nessun premio.
                    Le cifre si cambiano in <code>config/prizes.php</code>.
                </p>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Classifica</th>
                            @foreach (range(1, max(count($scale['monthly']), count($scale['midalario']))) as $pos)
                                <th>{{ $pos }}°</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kinds as $k => $name)
                            <tr>
                                <td class="fw-bold">{{ $name }}</td>
                                @foreach (range(1, max(count($scale['monthly']), count($scale['midalario']))) as $pos)
                                    <td>{{ isset($scale[$k][$pos - 1]) ? $fmt($scale[$k][$pos - 1]) : '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Prossimo premio mensile: {{ $monthLabel }}</h2>
            </div>
        </div>
        <div class="admin-card-body">
            @if ($monthDone)
                <div class="alert alert-secondary mb-0">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    I premi di <strong>{{ $monthLabel }}</strong> sono già stati assegnati il
                    {{ $monthDone->created_at->format('d/m/Y \a\l\l\e H:i') }}: li trovi nel registro qui sotto.
                </div>
            @else
                <div class="alert alert-info {{ empty($monthPreview) ? 'mb-0' : '' }}">
                    <i class="bi bi-clock-history"></i>
                    I premi di <strong>{{ $monthLabel }}</strong> verranno assegnati automaticamente il
                    <strong>{{ $nextRun->locale('it')->translatedFormat('d/m/Y \a\l\l\e H:i') }}</strong>.
                    @if (empty($monthPreview))
                        Al momento nessun utente avrebbe un premio (nessuna attività registrata in quel mese).
                    @else
                        In base ai punteggi attuali sarebbe così (anteprima, può cambiare):
                    @endif
                </div>
                @if (! empty($monthPreview))
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead>
                                <tr><th>#</th><th>Utente</th><th>Punteggio</th><th>Monete previste</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($monthPreview as $p)
                                    <tr>
                                        <td><span class="badge bg-primary">{{ $p['position'] }}</span></td>
                                        <td class="fw-bold">{{ $p['nickname'] }}</td>
                                        <td>{{ number_format($p['score'] / 100, 2, ',', '.') }}</td>
                                        <td><i class="bi bi-coin text-warning"></i> {{ $fmt($p['coins']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    </section>

    @if ($unrewarded->isNotEmpty())
        <section class="admin-card mb-3">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-section-title">Midalario conclusi senza premi in monete</h2>
                    <p class="admin-muted mb-0">
                        Sessioni chiuse prima dell'introduzione dei premi (o senza nessun punteggio): non sono state premiate e non lo saranno in automatico.
                    </p>
                </div>
            </div>
            <div class="admin-card-body">
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead>
                            <tr><th>Sessione</th><th>Data</th><th>Partecipanti</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($unrewarded as $quiz)
                                @php $when = $quiz->midalario_scheduled_at ?? $quiz->midalario_started_at; @endphp
                                <tr>
                                    <td class="fw-bold">{{ $quiz->title }}</td>
                                    <td>{{ $when ? \Carbon\Carbon::parse($when)->format('d/m/Y H:i') : '—' }}</td>
                                    <td>{{ $quiz->completed_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Registro dei premi assegnati</h2>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="d-flex gap-2 mb-3">
                <a href="{{ route('admin.prize-awards.index') }}" class="btn btn-sm {{ ! $kind ? 'btn-dark' : 'btn-outline-dark' }}">Tutti</a>
                <a href="{{ route('admin.prize-awards.index', ['kind' => 'monthly']) }}" class="btn btn-sm {{ $kind === 'monthly' ? 'btn-dark' : 'btn-outline-dark' }}">Classifica mensile</a>
                <a href="{{ route('admin.prize-awards.index', ['kind' => 'midalario']) }}" class="btn btn-sm {{ $kind === 'midalario' ? 'btn-dark' : 'btn-outline-dark' }}">Midalario</a>
            </div>

            @forelse ($events as $event)
                <div class="mb-4">
                    <h3 class="h6 mb-1">
                        <i class="bi {{ $event['kind'] === 'monthly' ? 'bi-calendar-month' : 'bi-broadcast' }}"></i>
                        {{ $event['label'] }}
                        <span class="badge bg-secondary">{{ $kinds[$event['kind']] }}</span>
                    </h3>
                    <p class="admin-muted mb-2">
                        Assegnato il {{ \Carbon\Carbon::parse($event['at'])->format('d/m/Y \a\l\l\e H:i') }}
                        · {{ count($event['rows']) }} premi · <i class="bi bi-coin text-warning"></i> {{ $fmt($event['total']) }} monete in totale
                    </p>
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead>
                                <tr><th>#</th><th>Utente</th><th>Monete</th><th>Avviso</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($event['rows'] as $r)
                                    <tr>
                                        <td><span class="badge bg-primary">{{ $r->position }}</span></td>
                                        <td class="fw-bold">{{ $r->nickname }}</td>
                                        <td><i class="bi bi-coin text-warning"></i> {{ $fmt($r->coins) }}</td>
                                        <td>
                                            @if ($r->seen_at)
                                                <span class="text-success"><i class="bi bi-check2"></i> Visto il {{ \Carbon\Carbon::parse($r->seen_at)->format('d/m/Y H:i') }}</span>
                                            @else
                                                <span class="admin-muted"><i class="bi bi-hourglass-split"></i> Non ancora visto</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="admin-empty">Nessun premio assegnato finora.</div>
            @endforelse
        </div>
    </section>
@endsection
