@php
    use App\Models\Prospect;

    // Link che mantiene i filtri attuali e cambia solo alcuni parametri.
    $keep = fn (array $extra = []) => array_filter(
        array_merge($filters, ['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage === 50 ? null : $perPage], $extra),
        fn ($v) => $v !== null && $v !== ''
    );
@endphp
@php
    $sortLink = function (string $col, string $label) use ($keep, $sort, $direction) {
        $isCurrent = $sort === $col;
        $nextDir = $isCurrent && $direction === 'asc' ? 'desc' : 'asc';
        $icon = $isCurrent ? ($direction === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>') : '';

        return '<a href="' . e(route('admin.prospects.index', $keep(['sort' => $col, 'direction' => $nextDir, 'page' => null]))) . '" class="text-decoration-none text-reset">' . e($label) . '</a>' . $icon;
    };
    $hasFilters = collect($filters)->filter(fn ($v) => $v !== '')->isNotEmpty();
@endphp

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
                                    <span class="badge {{ in_array($p->website_status, ['none', 'link_only', 'broken'], true) ? 'bg-success' : 'bg-secondary' }}">
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
                                    <form method="POST" action="{{ route('admin.prospects.status', $p) }}" class="d-grid gap-1 js-status-form">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="form-select form-select-sm" aria-label="Stato di {{ $p->instagram_handle }}">
                                            @foreach (Prospect::STATUSES as $key => $label)
                                                <option value="{{ $key }}" @selected($p->status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="date" name="follow_up_at" value="{{ optional($p->follow_up_at)->format('Y-m-d') }}"
                                            class="form-control form-control-sm" title="Promemoria: ricontattare entro" aria-label="Promemoria per {{ $p->instagram_handle }}">
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
                                        @unless (in_array($p->status, Prospect::CLOSED_STATUSES, true))
                                            <button type="button" class="btn btn-sm btn-success"
                                                data-copy-message="{{ $p->outreach_message }}" data-open-url="{{ $p->instagram_chat_url }}" data-open-url-mobile="{{ $p->instagram_chat_url_mobile }}"
                                                title="Copia il messaggio personalizzato e apre la chat Instagram in una nuova scheda">
                                                <i class="bi bi-clipboard"></i>
                                                Copia e apri chat
                                            </button>
                                        @endunless
                                        <a href="{{ route('admin.prospects.edit', $p) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                            Modifica
                                        </a>
                                        <form action="{{ route('admin.prospects.destroy', $p) }}" method="POST" class="js-delete-form">
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

            <div class="admin-card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="small admin-muted">
                    Mostrati {{ $prospects->firstItem() }}-{{ $prospects->lastItem() }} di {{ $prospects->total() }} profili
                    @if ($prospects->hasPages())
                        · pagina {{ $prospects->currentPage() }} di {{ $prospects->lastPage() }}
                    @endif
                </div>
                @if ($prospects->hasPages())
                    <div>{{ $prospects->links() }}</div>
                @endif
            </div>
        @endif
