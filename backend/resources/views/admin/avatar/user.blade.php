@extends('layouts.admin')

@section('title', 'Avatar di '.$user->nickname)
@section('kicker', 'Avatar e negozio')
@section('page-title', 'Avatar di '.$user->nickname)

@section('content')
    @php
        $sourceLabel = ['level' => 'livello', 'shop' => 'comprato', 'admin' => 'regalo admin'];
        $coinLabel = ['level' => 'Bonus livello', 'shop' => 'Acquisto', 'admin' => 'Admin', 'login' => 'Accesso giornaliero', 'challenge' => 'Sfida', 'quiz' => 'Quiz', 'minigioco' => 'Minigioco', 'training' => 'Training', 'midalario' => 'Midalario'];
    @endphp

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.avatar.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Avatar e negozio</a>
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-person"></i> Scheda utente</a>
    </div>

    <div class="admin-stat-group">
        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="" width="64" height="64" class="rounded-circle border border-2 border-dark mb-1">
                @else
                    <i class="bi bi-person-circle"></i>
                @endif
                <span>{{ $user->nickname }}</span>
                <strong>{{ $config ? 'Avatar salvato' : 'Nessun avatar' }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-star-fill"></i>
                <span>Livello</span>
                <strong>{{ $level }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-coin"></i>
                <span>Monete</span>
                <strong>{{ number_format($user->coins, 0, ',', '.') }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-box-seam"></i>
                <span>Oggetti posseduti</span>
                <strong>{{ $owned->count() }}</strong>
            </div>
        </div>
    </div>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <h2 class="admin-section-title">Regala a {{ $user->nickname }}</h2>
        </div>
        <div class="admin-card-body">
            @include('admin.avatar._gift', ['prefillUser' => $user->nickname])
        </div>
    </section>

    <div class="row g-3">
        <div class="col-lg-7">
            <section class="admin-card h-100">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">Oggetti posseduti</h2>
                        <p class="admin-muted mb-0">Togliere un oggetto comprato non restituisce le monete: se serve, regalale a parte.</p>
                    </div>
                </div>
                @if ($owned->isEmpty())
                    <div class="admin-card-body"><p class="admin-muted mb-0">Nessun oggetto: l'utente non ha ancora aperto la pagina avatar.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Oggetto</th>
                                    <th>Zona</th>
                                    <th>Come</th>
                                    <th>Indossato</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($owned as $row)
                                    @php $it = $row->item; @endphp
                                    @continue(! $it)
                                    <tr class="{{ $it->is_active ? '' : 'opacity-50' }}">
                                        <td>
                                            {{ $it->name }}
                                            @if (! $row->seen_at)
                                                <span class="badge bg-danger">nuovo</span>
                                            @endif
                                        </td>
                                        <td>{{ $slotLabels[$it->slot] ?? $it->slot }}</td>
                                        <td class="small">{{ $sourceLabel[$row->source] ?? $row->source }} · {{ $row->created_at?->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($config && ($config['equipped'][$it->slot] ?? null) === $it->key)
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($it->unlock_level !== 1)
                                                <form method="POST" action="{{ route('admin.avatar.revoke', [$user, $it]) }}"
                                                    onsubmit="return confirm('Togliere {{ addslashes($it->name) }} a {{ addslashes($user->nickname) }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Togli</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="col-lg-5">
            <section class="admin-card h-100">
                <div class="admin-card-header">
                    <h2 class="admin-section-title">Ultimi movimenti di monete</h2>
                </div>
                @if ($coinEvents->isEmpty())
                    <div class="admin-card-body"><p class="admin-muted mb-0">Nessun movimento.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <tbody>
                                @foreach ($coinEvents as $ev)
                                    <tr>
                                        <td class="small">{{ $ev->created_at?->format('d/m H:i') }}</td>
                                        <td class="small">{{ $coinLabel[$ev->source] ?? ucfirst($ev->source) }}</td>
                                        <td class="text-end fw-bold {{ $ev->amount < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $ev->amount > 0 ? '+' : '' }}{{ $ev->amount }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
