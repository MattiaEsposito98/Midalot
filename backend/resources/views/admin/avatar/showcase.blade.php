@extends('layouts.admin')

@section('title', 'Vetrina settimanale')
@section('kicker', 'Avatar e negozio')
@section('page-title', 'Vetrina settimanale')

@section('content')
    <a href="{{ route('admin.avatar.index') }}" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Tutti gli oggetti</a>

    <div class="alert alert-info">
        <strong><i class="bi bi-info-circle"></i> Come funziona la rotazione</strong>
        <ul class="mb-0 mt-1">
            <li>Ogni <b>lunedì alle 00:00</b> la vetrina cambia da sola: vengono scelti <b>{{ $count }} oggetti</b> tra quelli in vendita nel negozio con le monete (non serve fare nulla).</li>
            <li>La rotazione è un <b>giro ordinato</b>: ogni oggetto esce una volta, e solo quando sono usciti tutti il giro si rimescola e riparte. Così ogni settimana sono oggetti diversi.</li>
            <li>Gli oggetti in vetrina costano il <b>{{ $discount }}% in meno</b> fino a domenica notte. Finita la settimana tornano al prezzo normale ma restano nel negozio.</li>
            <li>Gli <b>esclusivi</b> non entrano mai in vetrina e non hanno sconti: si aprono e si chiudono dalla scheda dell'oggetto.</li>
            <li>Gli oggetti regalati con i livelli o con gli obiettivi (senza prezzo) non entrano in vetrina.</li>
            <li>Se vuoi, puoi <b>scegliere a mano</b> gli oggetti di questa settimana o della prossima. Togliendo tutte le scelte si torna alla rotazione automatica.</li>
            <li>Sconto e numero di oggetti si cambiano qui sotto in <b>Impostazioni di base</b>. Per periodi speciali (Natale, Black Friday...) crea una <b>promozione a tempo</b>: finita la data si torna da soli alle impostazioni di base.</li>
        </ul>
    </div>

    @if ($activePromo)
        <div class="alert alert-warning">
            <i class="bi bi-gift"></i> <b>Promozione attiva ora: {{ $activePromo['name'] }}</b> — {{ $activePromo['discount'] }}% di sconto
            {{ $activePromo['scope'] === 'all' ? 'su tutto il negozio' : 'sugli oggetti in vetrina' }},
            fino al {{ \Illuminate\Support\Carbon::parse($activePromo['ends_at'])->format('d/m/Y H:i') }}.
        </div>
    @endif

    <section class="admin-card mb-4">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Impostazioni di base</h2>
                <p class="admin-muted mb-0">Valgono sempre, quando non c'è nessuna promozione. Di partenza: {{ $defaults['count'] }} oggetti, {{ $defaults['discount'] }}% di sconto.</p>
            </div>
        </div>
        <div class="admin-card-body">
            <form method="POST" action="{{ route('admin.avatar.settings.save') }}" class="row g-3 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-4">
                    <label class="form-label">Oggetti in vetrina ogni settimana</label>
                    <input type="number" name="count" class="form-control" min="1" max="12" value="{{ old('count', $count) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sconto della vetrina (%)</label>
                    <input type="number" name="discount" class="form-control" min="1" max="90" value="{{ old('discount', $discount) }}" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
                <div class="form-text">Cambiando il numero di oggetti la rotazione si ricalcola subito.</div>
            </form>
        </div>
    </section>

    <section class="admin-card mb-4">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Promozioni a tempo</h2>
                <p class="admin-muted mb-0">Es. «Saldi di Natale» -50% dal 20/12 al 6/1. Se ce ne sono più di una insieme vale lo sconto più alto.</p>
            </div>
        </div>
        <div class="admin-card-body">
            <form method="POST" action="{{ route('admin.avatar.promo.store') }}" class="row g-3 mb-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Nome</label>
                    <input type="text" name="name" class="form-control" maxlength="80" placeholder="Saldi di Natale" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Sconto %</label>
                    <input type="number" name="discount" class="form-control" min="1" max="90" value="{{ old('discount', 50) }}" required>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label">Vale per</label>
                    <select name="scope" class="form-select">
                        <option value="showcase" @selected(old('scope') === 'showcase')>Solo la vetrina</option>
                        <option value="all" @selected(old('scope') === 'all')>Tutto il negozio</option>
                    </select>
                </div>
                <div class="col-md-3"></div>
                <div class="col-md-4">
                    <label class="form-label">Inizio</label>
                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fine</label>
                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at') }}" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Crea promozione</button>
                </div>
                <div class="form-text">Gli esclusivi non hanno mai sconti, nemmeno nelle promozioni.</div>
            </form>

            @if ($promotions->isEmpty())
                <p class="admin-muted mb-0">Nessuna promozione.</p>
            @else
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead><tr><th>Nome</th><th>Sconto</th><th>Vale per</th><th>Periodo</th><th>Stato</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($promotions as $p)
                                @php
                                    $now = now();
                                    $live = $p->is_active && $p->starts_at <= $now && $p->ends_at > $now;
                                @endphp
                                <tr>
                                    <td class="fw-bold">{{ $p->name }}</td>
                                    <td>{{ $p->discount }}%</td>
                                    <td>{{ $p->scope === 'all' ? 'Tutto il negozio' : 'Vetrina' }}</td>
                                    <td>{{ $p->starts_at->format('d/m/Y H:i') }} → {{ $p->ends_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if (! $p->is_active)
                                            <span class="badge bg-secondary">Sospesa</span>
                                        @elseif ($live)
                                            <span class="badge bg-success">In corso</span>
                                        @elseif ($p->starts_at > $now)
                                            <span class="badge bg-info text-dark">Programmata</span>
                                        @else
                                            <span class="badge bg-secondary">Finita</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <form method="POST" action="{{ route('admin.avatar.promo.toggle', $p->id) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary">{{ $p->is_active ? 'Sospendi' : 'Riattiva' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.avatar.promo.delete', $p->id) }}" class="d-inline" onsubmit="return confirm('Eliminare la promozione?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Elimina</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    @foreach ([['this', 'Questa settimana'], ['next', 'Prossima settimana']] as [$key, $label])
        @php $w = $weeks[$key]; @endphp
        <section class="admin-card mb-4">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-section-title">{{ $label }}</h2>
                    <p class="admin-muted mb-0">
                        Dal {{ $w['start']->format('d/m') }} al {{ $w['start']->copy()->addDays(6)->format('d/m') }} ·
                        @if ($w['manual'])
                            <span class="badge bg-warning text-dark">Scelta a mano</span>
                        @else
                            <span class="badge bg-success">Rotazione automatica</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="admin-card-body">
                <div class="row g-2 mb-3">
                    @forelse ($w['items'] as $item)
                        @php $sale = (int) round($item->price * (100 - max($discount, $activePromo['discount'] ?? 0)) / 100); @endphp
                        <div class="col-md-3 col-6">
                            <div class="border rounded p-2 h-100">
                                <div class="fw-bold">{{ $item->name }}</div>
                                <div class="small admin-muted">{{ $slotLabels[$item->slot] ?? $item->slot }} · {{ ucfirst($item->rarity) }}</div>
                                <div><s class="admin-muted">{{ $item->price }}</s> <i class="bi bi-coin text-warning"></i> <b>{{ $sale }}</b></div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 admin-muted">Nessun oggetto: non c'è niente in vendita nel negozio.</div>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.avatar.showcase.save') }}">
                    @csrf
                    <input type="hidden" name="week" value="{{ $key }}">
                    <details>
                        <summary class="fw-bold mb-2" style="cursor:pointer">Scegli a mano gli oggetti di questa settimana</summary>
                        <div class="form-text mb-2">Spunta gli oggetti da mettere in vetrina (consigliati {{ $count }}). Nessuna spunta = rotazione automatica.</div>
                        <div class="row g-1" style="max-height:280px;overflow:auto">
                            @foreach ($shopItems as $opt)
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-check">
                                        <input type="checkbox" name="items[]" value="{{ $opt->key }}" class="form-check-input"
                                            @checked($w['manual'] && in_array($opt->key, $w['keys'], true))>
                                        <span class="form-check-label">{{ $opt->name }} <small class="admin-muted">({{ $opt->price }})</small></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm mt-3">Salva vetrina</button>
                    </details>
                </form>
            </div>
        </section>
    @endforeach
@endsection
