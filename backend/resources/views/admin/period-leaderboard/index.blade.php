@extends('layouts.admin')

@section('title', 'Classifica premi')
@section('kicker', 'Classifiche')
@section('page-title', 'Classifica premi')

@section('content')
    @php
        $monthNames = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
        ];
    @endphp

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Premio "Vincitore del mese"</h2>
                <p class="admin-muted mb-0">
                    Il cron automatico non è ancora attivabile su questo hosting: finché non lo sarà, questo bottone
                    assegna manualmente il premio del mese scorso, una sola volta al mese.
                </p>
            </div>
        </div>

        <div class="admin-card-body">
            @if ($badgeRun)
                @php
                    $nextTargetMonth = $badgeTargetMonth->copy()->addMonth();
                    $availableFrom = $nextTargetMonth->copy()->addMonth()->startOfMonth();
                @endphp
                <div class="alert alert-secondary mb-0">
                    <p class="mb-1">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        Il premio di <strong>{{ Illuminate\Support\Str::ucfirst($badgeTargetMonth->locale('it')->translatedFormat('F Y')) }}</strong>
                        è già stato assegnato il {{ $badgeRun->created_at->format('d/m/Y \a\l\l\e H:i') }}
                        @if ($badgeRun->triggeredBy)
                            da {{ $badgeRun->triggeredBy->nickname }}
                        @else
                            automaticamente
                        @endif
                        .
                    </p>
                    <p class="mb-0 admin-muted">
                        Il prossimo premio assegnabile sarà quello di <strong>{{ Illuminate\Support\Str::ucfirst($nextTargetMonth->locale('it')->translatedFormat('F Y')) }}</strong>,
                        disponibile a partire dal 1° {{ Illuminate\Support\Str::ucfirst($availableFrom->locale('it')->translatedFormat('F Y')) }}.
                    </p>
                </div>
            @else
                @php $monthLabel = Illuminate\Support\Str::ucfirst($badgeTargetMonth->locale('it')->translatedFormat('F Y')); @endphp
                <div class="alert alert-warning">
                    <p class="mb-1">
                        Premendo il bottone assegnerai <strong>ora</strong> il premio "Vincitore del mese" per
                        <strong>{{ $monthLabel }}</strong> (il mese scorso rispetto ad oggi).
                    </p>
                    @if (empty($badgePreview['winners']))
                        <p class="mb-0">Al momento non risulta nessuna attività registrata in quel mese: nessun badge verrebbe assegnato.</p>
                    @else
                        <p class="mb-0">
                            In base ai punteggi attuali,
                            {{ count($badgePreview['winners']) > 1 ? 'i vincitori sarebbero' : 'il vincitore sarebbe' }}:
                            @foreach ($badgePreview['winners'] as $winner)
                                <strong>{{ $winner['nickname'] }}</strong> ({{ number_format($winner['total_score'] / 100, 2, ',', '.') }} punti){{ !$loop->last ? ',' : '' }}
                            @endforeach
                        </p>
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.period-leaderboard.assign-monthly-badge') }}"
                    onsubmit="return confirm('Confermi di voler assegnare ORA il premio Vincitore del mese per {{ $monthLabel }}? Una volta fatto non potrai rifarlo prima del mese prossimo.')">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-award-fill"></i>
                        Assegna il premio di {{ $monthLabel }}
                    </button>
                </form>
            @endif
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Classifica settimanale/mensile</h2>
                <p class="admin-muted mb-0">
                    Punti da Quiz One Shot e Minigiochi. Il Training ha una classifica propria per categoria/singolo training e non contribuisce qui.
                </p>
            </div>
        </div>

        <div class="admin-card-body">
            <div class="d-flex gap-2 mb-3">
                <a href="{{ route('admin.period-leaderboard.index', ['tab' => 'weekly']) }}"
                    class="btn btn-sm {{ $tab === 'weekly' ? 'btn-dark' : 'btn-outline-dark' }}">
                    Settimanale
                </a>
                <a href="{{ route('admin.period-leaderboard.index', ['tab' => 'monthly']) }}"
                    class="btn btn-sm {{ $tab === 'monthly' ? 'btn-dark' : 'btn-outline-dark' }}">
                    Mensile
                </a>
            </div>

            <form method="GET" action="{{ route('admin.period-leaderboard.index') }}" class="mb-3" style="max-width: 320px;">
                <input type="hidden" name="tab" value="{{ $tab }}">

                @if ($tab === 'weekly')
                    <select name="week" class="form-select" onchange="this.form.submit()">
                        @foreach ($weeks as $week)
                            @php $weekEnd = \Carbon\Carbon::parse($week)->addDays(6); @endphp
                            <option value="{{ $week }}" {{ $week === $selectedWeek ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($week)->format('d/m') }} - {{ $weekEnd->format('d/m/Y') }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <select name="month" class="form-select" onchange="this.form.submit()">
                        @foreach ($months as $month)
                            @php [$y, $m] = explode('-', $month); @endphp
                            <option value="{{ $month }}" {{ $month === $selectedMonth ? 'selected' : '' }}>
                                {{ $monthNames[(int) $m] }} {{ $y }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </form>

            <p class="admin-muted">{{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</p>

            @if ($results->isEmpty())
                <div class="admin-empty">Nessun risultato per questo periodo.</div>
            @else
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Utente</th>
                                <th>Punteggio totale</th>
                                <th>Attività completate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results as $r)
                                <tr>
                                    <td><span class="badge bg-primary">{{ $r['position'] }}</span></td>
                                    <td class="fw-bold">
                                        {{ $r['nickname'] }}
                                        @foreach ($r['badges'] ?? [] as $badge)
                                            <span class="badge bg-warning text-dark" title="{{ $badge['label'] }}">
                                                <i class="bi bi-{{ $badge['type'] === 'midalario' ? 'broadcast' : 'award-fill' }}"></i>
                                                {{ $badge['label'] }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td><strong>{{ number_format($r['total_score'] / 100, 2, ',', '.') }}</strong></td>
                                    <td>{{ $r['quizzes_completed'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endsection
