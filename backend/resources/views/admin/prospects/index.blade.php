@extends('layouts.admin')

@section('title', 'Clienti da contattare')
@section('kicker', 'Servizi digitali')
@section('page-title', 'Clienti da contattare')


@php
    use App\Models\Prospect;

    $exportParams = array_filter($filters, fn ($v) => $v !== '');
    $hasFilters = collect($filters)->filter(fn ($v) => $v !== '')->isNotEmpty();
@endphp

@section('content')
    <div class="alert alert-light border small">
        <i class="bi bi-shield-check"></i>
        <strong>Solo dati pubblici di attività.</strong> Salva solo ciò che l'attività mostra pubblicamente nel profilo.
        Email e telefono servono per <strong>messaggi singoli e personali</strong> (mai invii in massa), dicendo come hai trovato il contatto;
        il canale principale è il messaggio privato Instagram. Nessun invio automatico: questa pagina serve solo a tenere traccia.
        Se qualcuno chiede di essere tolto, elimina il profilo. Non è consulenza legale (Codice Privacy art. 130, GDPR).
    </div>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Profili da contattare</h2>
                <p class="admin-muted mb-0"><span id="prospects-total-all">{{ $totalAll }}</span> profili in elenco · filtri e ordinamento fatti dal database.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.prospect-searches.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-map"></i>
                    Ricerche e copertura
                </a>
                <a href="{{ route('admin.campaign-visits.index') }}" class="btn btn-outline-secondary btn-sm"
                    title="Quante volte è stato aperto il link nei messaggi (contatore anonimo)">
                    <i class="bi bi-cursor"></i>
                    Aperture link
                </a>
                <a href="{{ route('admin.prospects.export', $exportParams) }}" id="prospects-export" class="btn btn-outline-secondary btn-sm"
                    title="Esporta in Excel solo i profili che vedi con i filtri attuali">
                    <i class="bi bi-file-earmark-excel"></i>
                    <span>Esporta Excel{{ $hasFilters ? ' (' . $prospects->total() . ' filtrati)' : '' }}</span>
                </a>
                <a href="{{ route('admin.prospects.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i>
                    Aggiungi profilo
                </a>
            </div>
        </div>


        <div class="admin-card-body">
            <div id="prospects-tabs">
                @include('admin.prospects._tabs')
            </div>

            <form method="GET" action="{{ route('admin.prospects.index') }}" id="prospects-filters" class="row g-2 align-items-center">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <div class="col-auto flex-grow-1" style="min-width: 200px;">
                    <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control"
                        placeholder="Cerca per profilo, nome o note...">
                </div>
                <div class="col-auto">
                    <select class="form-select" name="region">
                        <option value="">Tutte le regioni</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region }}" @selected($filters['region'] === $region)>{{ $region }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="city">
                        <option value="">Tutte le città</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city }}" @selected($filters['city'] === $city)>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="sector">
                        <option value="">Tutti i settori</option>
                        @foreach ($sectors as $sector)
                            <option value="{{ $sector }}" @selected($filters['sector'] === $sector)>{{ $sector }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="priority">
                        <option value="">Ogni priorità</option>
                        @foreach (Prospect::PRIORITIES as $key => $label)
                            <option value="{{ $key }}" @selected($filters['priority'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="proposal">
                        <option value="">Ogni proposta</option>
                        @foreach (Prospect::PROPOSALS as $key => $label)
                            <option value="{{ $key }}" @selected($filters['proposal'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="website_status">
                        <option value="">Sito: tutti</option>
                        @foreach (Prospect::WEBSITE_STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($filters['website_status'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="activity">
                        <option value="">Attività: tutte</option>
                        @foreach (Prospect::ACTIVITIES as $key => $label)
                            <option value="{{ $key }}" @selected($filters['activity'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="contacted">
                        <option value="">Contatto: tutti</option>
                        <option value="no" @selected($filters['contacted'] === 'no')>Mai contattati</option>
                        <option value="yes" @selected($filters['contacted'] === 'yes')>Già contattati</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="follow_up">
                        <option value="">Promemoria: tutti</option>
                        <option value="due" @selected($filters['follow_up'] === 'due')>Scaduti o di oggi</option>
                        <option value="upcoming" @selected($filters['follow_up'] === 'upcoming')>Futuri</option>
                        <option value="none" @selected($filters['follow_up'] === 'none')>Senza promemoria</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="search_id">
                        <option value="">Ogni ricerca</option>
                        @foreach ($searches as $search)
                            <option value="{{ $search->id }}" @selected($filters['search_id'] === (string) $search->id)>{{ $search->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <input type="number" name="min_followers" value="{{ $filters['min_followers'] }}" min="0" class="form-control" style="width: 130px;" placeholder="Follower min">
                </div>
                <div class="col-auto">
                    <input type="number" name="max_followers" value="{{ $filters['max_followers'] }}" min="0" class="form-control" style="width: 130px;" placeholder="Follower max">
                </div>
                <div class="col-auto">
                    <select class="form-select" name="per_page" aria-label="Profili per pagina">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} per pagina</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.prospects.index') }}" id="prospects-reset" class="btn btn-outline-secondary" @unless ($hasFilters) hidden @endunless>Azzera</a>
                </div>
                <noscript>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Filtra</button>
                    </div>
                </noscript>
            </form>
        </div>

        <div id="prospects-table">
            @include('admin.prospects._table')
        </div>
    </section>

    @include('admin.prospects._copy-script')
    @include('admin.prospects._ajax-script')
@endsection
