@extends('layouts.admin')

@section('title', 'Clienti da contattare')
@section('kicker', 'Servizi digitali')
@section('page-title', 'Clienti da contattare')

@php
    use App\Models\Prospect;

    // Link che mantiene i filtri attuali e cambia solo alcuni parametri.
    $keep = fn (array $extra = []) => array_filter(
        array_merge($filters, ['sort' => $sort, 'direction' => $direction], $extra),
        fn ($v) => $v !== null && $v !== ''
    );
    $sortLink = function (string $col, string $label) use ($keep, $sort, $direction) {
        $isCurrent = $sort === $col;
        $nextDir = $isCurrent && $direction === 'asc' ? 'desc' : 'asc';
        $icon = $isCurrent ? ($direction === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>') : '';

        return '<a href="' . e(route('admin.prospects.index', $keep(['sort' => $col, 'direction' => $nextDir, 'page' => null]))) . '" class="text-decoration-none text-reset">' . e($label) . '</a>' . $icon;
    };
    $statusColors = [
        'to_contact' => 'bg-secondary',
        'contacted' => 'bg-info text-dark',
        'replied' => 'bg-primary',
        'interested' => 'bg-warning text-dark',
        'quote_sent' => 'bg-warning text-dark',
        'client' => 'bg-success',
        'not_suitable' => 'bg-dark',
    ];
    $hasFilters = collect($filters)->filter(fn ($v) => $v !== '')->isNotEmpty();
    $exportParams = array_filter($filters, fn ($v) => $v !== '');
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
                <p class="admin-muted mb-0">{{ $totalAll }} profili in elenco · filtri e ordinamento fatti dal database.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.prospect-searches.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-map"></i>
                    Ricerche e copertura
                </a>
                <a href="{{ route('admin.prospects.export', $exportParams) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download"></i>
                    Esporta CSV
                </a>
                <a href="{{ route('admin.prospects.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i>
                    Aggiungi profilo
                </a>
            </div>
        </div>

        <div class="admin-card-body">
            <div class="admin-page-actions mb-3">
                <a href="{{ route('admin.prospects.index', $keep(['status' => null, 'follow_up' => null, 'page' => null])) }}"
                    class="btn btn-sm {{ $filters['status'] === '' && $filters['follow_up'] !== 'due' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Tutti <span class="badge bg-secondary ms-1">{{ $statusCounts->sum() }}</span>
                </a>
                @foreach (Prospect::STATUSES as $key => $label)
                    <a href="{{ route('admin.prospects.index', $keep(['status' => $key, 'page' => null])) }}"
                        class="btn btn-sm {{ $filters['status'] === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }} <span class="badge bg-secondary ms-1">{{ $statusCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
                <a href="{{ route('admin.prospects.index', $keep(['follow_up' => 'due', 'status' => null, 'page' => null])) }}"
                    class="btn btn-sm {{ $filters['follow_up'] === 'due' ? 'btn-danger' : 'btn-outline-danger' }}">
                    <i class="bi bi-alarm"></i> Da ricontattare <span class="badge bg-danger ms-1">{{ $dueCount }}</span>
                </a>
            </div>

            <form method="GET" action="{{ route('admin.prospects.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                @if ($filters['status'] !== '')
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                @endif
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
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                        Filtra
                    </button>
                </div>
                @if ($hasFilters)
                    <div class="col-auto">
                        <a href="{{ route('admin.prospects.index') }}" class="btn btn-outline-secondary">Azzera</a>
                    </div>
                @endif
            </form>
        </div>

        @if ($prospects->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-person-lines-fill fs-1 d-block mb-2"></i>
                    <p class="mb-3">{{ $hasFilters ? 'Nessun profilo per questi filtri.' : 'Nessun profilo ancora in elenco.' }}</p>
                    <a href="{{ route('admin.prospects.create') }}" class="btn btn-primary">Aggiungi profilo</a>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Profilo</th>
                            <th>{!! $sortLink('followers', 'Follower') !!}</th>
                            <th>{!! $sortLink('region', 'Zona') !!} / Settore</th>
                            <th>Sito e segnali</th>
                            <th>{!! $sortLink('priority', 'Priorità') !!}</th>
                            <th>Stato e promemoria</th>
                            <th>{!! $sortLink('contacted_at', 'Contattato') !!}</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prospects as $p)
                            <tr>
                                <td>
                                    <a href="{{ $p->instagram_url }}" target="_blank" rel="noopener noreferrer" class="fw-bold">
                                        <i class="bi bi-instagram"></i> {{ $p->instagram_handle }}
                                    </a>
                                    @if ($p->name)
                                        <div class="small">{{ $p->name }}</div>
                                    @endif
                                    @if ($p->search)
                                        <div class="small admin-muted">{{ $p->search->name }}</div>
                                    @endif
                                </td>
                                <td>{{ $p->followers !== null ? number_format($p->followers, 0, ',', '.') : '-' }}</td>
                                <td>
                                    {{ collect([$p->city, $p->region])->filter()->implode(', ') ?: '-' }}
                                    @if ($p->sector)
                                        <div class="small admin-muted">{{ $p->sector }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    <span class="badge {{ $p->website_status === 'none' || $p->website_status === 'link_only' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ Prospect::WEBSITE_STATUSES[$p->website_status] ?? $p->website_status }}
                                    </span>
                                    @if ($p->activity === 'active')
                                        <span class="badge bg-success">Attivo</span>
                                    @elseif ($p->activity === 'inactive')
                                        <span class="badge bg-dark">Poco attivo</span>
                                    @endif
                                    @if ($p->has_physical_store)
                                        <span class="badge bg-info text-dark">Negozio fisico</span>
                                    @endif
                                    @if ($p->orders_in_dm)
                                        <span class="badge bg-info text-dark">Ordini in DM</span>
                                    @endif
                                    @if ($p->proposal)
                                        <span class="badge bg-primary">{{ Prospect::PROPOSALS[$p->proposal] ?? $p->proposal }}</span>
                                    @endif
                                    @if ($p->website_url)
                                        <div><a href="{{ $p->website_url }}" target="_blank" rel="noopener noreferrer nofollow">{{ \Illuminate\Support\Str::limit(preg_replace('#^https?://(www\.)?#i', '', $p->website_url), 32) }}</a></div>
                                    @endif
                                    @if ($p->email || $p->phone)
                                        <div class="admin-muted">{{ collect([$p->email, $p->phone])->filter()->implode(' · ') }}</div>
                                    @endif
                                    @if ($p->signals)
                                        <div class="admin-muted">{{ \Illuminate\Support\Str::limit($p->signals, 80) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $p->priority === 'A' ? 'bg-danger' : ($p->priority === 'B' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ $p->priority }}</span>
                                </td>
                                <td style="min-width: 190px;">
                                    <form method="POST" action="{{ route('admin.prospects.status', $p) }}" class="d-grid gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Stato di {{ $p->instagram_handle }}">
                                            @foreach (Prospect::STATUSES as $key => $label)
                                                <option value="{{ $key }}" @selected($p->status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="date" name="follow_up_at" value="{{ optional($p->follow_up_at)->format('Y-m-d') }}"
                                            class="form-control form-control-sm" onchange="this.form.submit()" title="Promemoria: ricontattare entro" aria-label="Promemoria per {{ $p->instagram_handle }}">
                                    </form>
                                    @if ($p->isFollowUpDue())
                                        <span class="badge bg-danger mt-1"><i class="bi bi-alarm"></i> Da ricontattare</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($p->contacted_at)
                                        {{ $p->contacted_at->format('d/m/Y') }}
                                        @if ($p->contactedBy)
                                            <div class="admin-muted">da {{ $p->contactedBy->name }}</div>
                                        @endif
                                    @else
                                        <span class="admin-muted">Mai</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="{{ route('admin.prospects.edit', $p) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                            Modifica
                                        </a>
                                        <form action="{{ route('admin.prospects.destroy', $p) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Eliminare definitivamente {{ $p->instagram_handle }} dall\'elenco?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($prospects->hasPages())
                <div class="admin-card-body">
                    {{ $prospects->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
