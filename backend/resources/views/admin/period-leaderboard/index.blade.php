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
                                        @if ($r['badge'] ?? null)
                                            <span class="badge bg-warning text-dark" title="{{ $r['badge'] }}">
                                                <i class="bi bi-award-fill"></i> {{ $r['badge'] }}
                                            </span>
                                        @endif
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
