@extends('layouts.admin')

@section('title', 'Aperture link')
@section('kicker', 'Clienti da contattare')
@section('page-title', 'Aperture dei link nei messaggi')
@section('activeNav', 'prospects')

@section('content')
    <div class="alert alert-light border small">
        <i class="bi bi-shield-check"></i>
        Contatore <strong>anonimo</strong>: conta quante volte viene aperto l'indirizzo breve dei messaggi (<code>midalot.live/ig/&lt;settore&gt;</code>),
        per giorno e per settore. Non salva IP, cookie né altro che identifichi chi apre il link: non si può sapere chi ha cliccato.
        Non contano le aperture di admin e utenti del team quando sono collegati al sito; contano invece le prove da non collegati e le aperture ripetute della stessa persona.
        Chi non risponde non vuol dire che non abbia guardato.
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="admin-card h-100"><div class="admin-card-body">
                <div class="admin-muted small">Aperture totali</div>
                <div class="fs-3 fw-bold">{{ number_format($grandTotal, 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-card h-100"><div class="admin-card-body">
                <div class="admin-muted small">Ultimi 7 giorni</div>
                <div class="fs-3 fw-bold">{{ number_format($weekTotal, 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-card h-100"><div class="admin-card-body">
                <div class="admin-muted small">Profili contattati</div>
                <div class="fs-3 fw-bold">{{ number_format($contactedTotal, 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3 d-flex align-items-stretch">
            <a href="{{ route('admin.prospects.index') }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center">
                <i class="bi bi-arrow-left me-2"></i> Torna ai clienti
            </a>
        </div>
    </div>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Per settore</h2>
                <p class="admin-muted mb-0">Il settore è quello scritto nel link (campagna).</p>
            </div>
        </div>

        @if ($perCampaign->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-cursor fs-1 d-block mb-2"></i>
                    Nessuna apertura ancora registrata. Il contatore parte quando qualcuno apre un link <code>midalot.live/ig/...</code>.
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Campagna</th>
                            <th>Aperture totali</th>
                            <th>Ultimi 7 giorni</th>
                            <th>Ultima apertura</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perCampaign as $row)
                            <tr>
                                <td><code>{{ $row->campaign }}</code></td>
                                <td>{{ number_format((int) $row->total, 0, ',', '.') }}</td>
                                <td>{{ number_format((int) $row->week, 0, ',', '.') }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->last_day)->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Ultimi {{ $daysCount }} giorni</h2>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr><th>Giorno</th><th>Aperture</th></tr>
                </thead>
                <tbody>
                    @foreach ($days->reverse() as $d)
                        <tr>
                            <td>{{ $d['date']->format('d/m/Y') }}</td>
                            <td>
                                {{ $d['hits'] }}
                                @if ($d['hits'] > 0)
                                    <span class="d-inline-block bg-primary align-middle ms-2" style="height: 8px; width: {{ min(240, $d['hits'] * 12) }}px; border-radius: 4px;"></span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
