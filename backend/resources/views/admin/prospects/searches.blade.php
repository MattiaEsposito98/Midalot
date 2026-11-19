@extends('layouts.admin')

@section('title', 'Ricerche clienti')
@section('kicker', 'Clienti da contattare')
@section('page-title', 'Ricerche e copertura')
@section('activeNav', 'prospects')

@php
    use App\Models\ProspectSearch;

    $statusBadge = [
        'planned' => 'bg-secondary',
        'in_progress' => 'bg-warning text-dark',
        'done' => 'bg-success',
    ];
    $coveredRegions = $coverage->pluck('region')->all();
    $missingRegions = array_values(array_diff($regions, $coveredRegions));
@endphp

@section('content')
    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Nuova ricerca</h2>
                <p class="admin-muted mb-0">Una ricerca è un giro di raccolta profili (regione, città, settore, data): serve a sapere cosa è già stato fatto.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.prospects.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Torna ai clienti
                </a>
            </div>
        </div>

        <div class="admin-card-body">
            <form action="{{ route('admin.prospect-searches.store') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <label class="form-label small mb-1" for="s_region">Regione</label>
                    <select id="s_region" name="region" class="form-select">
                        <option value="">-</option>
                        @foreach ($regions as $r)
                            <option value="{{ $r }}" @selected(old('region') === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1" for="s_city">Città</label>
                    <input type="text" id="s_city" name="city" class="form-control" maxlength="80" value="{{ old('city') }}" placeholder="Scrivi 3 lettere">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1" for="s_sector">Settore</label>
                    <select id="s_sector" name="sector" class="form-select" required>
                        <option value="">-</option>
                        @foreach ($sectors as $sector)
                            <option value="{{ $sector }}" @selected(old('sector') === $sector)>{{ $sector }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1" for="s_status">Stato</label>
                    <select id="s_status" name="status" class="form-select" required>
                        @foreach (ProspectSearch::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', 'planned') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1" for="s_date">Data</label>
                    <input type="date" id="s_date" name="searched_at" class="form-control" value="{{ old('searched_at') }}">
                </div>
                <div class="col-auto flex-grow-1" style="min-width: 200px;">
                    <label class="form-label small mb-1" for="s_name">Nome (facoltativo)</label>
                    <input type="text" id="s_name" name="name" class="form-control" maxlength="150" value="{{ old('name') }}" placeholder="Se vuoto: Regione · Città · Settore · data">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Aggiungi ricerca
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Copertura per regione</h2>
                <p class="admin-muted mb-0">Ricerche fatte, profili trovati e profili già contattati.</p>
            </div>
        </div>

        @if ($coverage->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-map fs-1 d-block mb-2"></i>
                    Nessuna ricerca con regione ancora registrata.
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Regione</th>
                            <th>Ricerche</th>
                            <th>Fatte</th>
                            <th>Profili trovati</th>
                            <th>Contattati</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coverage as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.prospect-searches.index', ['region' => $row->region]) }}">{{ $row->region }}</a>
                                </td>
                                <td>{{ $row->searches }}</td>
                                <td>
                                    {{ $row->done_searches }}
                                    @if ((int) $row->done_searches === (int) $row->searches)
                                        <span class="badge bg-success">tutte fatte</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.prospects.index', ['region' => $row->region]) }}">{{ $row->prospects }}</a>
                                </td>
                                <td>{{ $row->contacted }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($missingRegions)
            <div class="admin-card-body small admin-muted">
                <strong>Regioni ancora senza ricerche:</strong> {{ implode(', ', $missingRegions) }}.
            </div>
        @endif
    </section>

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Ricerche</h2>
                <p class="admin-muted mb-0">Da fare, in corso e fatte. Eliminando una ricerca i profili restano nell'elenco.</p>
            </div>
        </div>

        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.prospect-searches.index') }}" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="region" class="form-select">
                        <option value="">Tutte le regioni</option>
                        @foreach ($regions as $r)
                            <option value="{{ $r }}" @selected($filters['region'] === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select">
                        <option value="">Tutti gli stati</option>
                        @foreach (ProspectSearch::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                        Filtra
                    </button>
                </div>
                @if ($filters['region'] !== '' || $filters['status'] !== '')
                    <div class="col-auto">
                        <a href="{{ route('admin.prospect-searches.index') }}" class="btn btn-outline-secondary">Azzera</a>
                    </div>
                @endif
            </form>
        </div>

        @if ($searches->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-search fs-1 d-block mb-2"></i>
                    Nessuna ricerca per questi filtri.
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Ricerca</th>
                            <th>Data</th>
                            <th>Profili</th>
                            <th>Contattati</th>
                            <th>Stato e note</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($searches as $s)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.prospects.index', ['search_id' => $s->id]) }}" class="fw-bold">{{ $s->name }}</a>
                                    <div class="small admin-muted">{{ collect([$s->region, $s->city, $s->sector])->filter()->implode(' · ') }}</div>
                                </td>
                                <td>{{ $s->searched_at ? $s->searched_at->format('d/m/Y') : '-' }}</td>
                                <td>{{ $s->prospects_count }}</td>
                                <td>{{ $s->contacted_count }}</td>
                                <td style="min-width: 260px;">
                                    <form action="{{ route('admin.prospect-searches.update', $s) }}" method="POST" class="d-grid gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <div class="d-flex gap-1 align-items-center">
                                            <span class="badge {{ $statusBadge[$s->status] ?? 'bg-secondary' }}">{{ ProspectSearch::STATUSES[$s->status] ?? $s->status }}</span>
                                            <select name="status" class="form-select form-select-sm" aria-label="Stato della ricerca">
                                                @foreach (ProspectSearch::STATUSES as $key => $label)
                                                    <option value="{{ $key }}" @selected($s->status === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <input type="text" name="notes" value="{{ $s->notes }}" maxlength="2000" class="form-control form-control-sm" placeholder="Note" aria-label="Note della ricerca">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-save"></i>
                                            Salva
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form action="{{ route('admin.prospect-searches.destroy', $s) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Eliminare questa ricerca? I {{ $s->prospects_count }} profili collegati restano nell\'elenco.')">
                                            <i class="bi bi-trash"></i>
                                            Elimina
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($searches->hasPages())
                <div class="admin-card-body">
                    {{ $searches->links() }}
                </div>
            @endif
        @endif
    </section>

    @include('admin.prospects._city-autocomplete', ['cityId' => 's_city', 'regionId' => 's_region'])
@endsection
