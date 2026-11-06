@extends('layouts.admin')

@section('title', 'Avatar e negozio')
@section('kicker', 'Giochi')
@section('page-title', 'Avatar e negozio')

@section('content')
    @php
        $s = $stats;
        $rarityClass = [
            'base' => 'bg-light text-dark border',
            'comune' => 'bg-secondary',
            'raro' => 'bg-primary',
            'epico' => 'bg-purple text-white',
            'leggendario' => 'bg-warning text-dark',
        ];
    @endphp

    <style>
        .bg-purple { background: #9b4dde; }
    </style>

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Panoramica</h2>
        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                <i class="bi bi-box-seam"></i>
                <span>Oggetti attivi</span>
                <strong>{{ $s['items_active'] }} <small class="text-muted">/ {{ $s['items_total'] }}</small></strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-shop"></i>
                <span>In vendita nel negozio</span>
                <strong>{{ $s['items_shop'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-person-badge"></i>
                <span>Utenti con avatar salvato</span>
                <strong>{{ $s['avatars_saved'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-image"></i>
                <span>Con ritratto</span>
                <strong>{{ $s['portraits'] }}</strong>
            </div>
        </div>
    </div>

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Monete e acquisti</h2>
        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                <i class="bi bi-coin"></i>
                <span>Monete in circolazione</span>
                <strong>{{ number_format($s['coins_circulating'], 0, ',', '.') }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Monete guadagnate giocando</span>
                <strong>{{ number_format($s['coins_earned'], 0, ',', '.') }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-cart-check"></i>
                <span>Monete spese nel negozio</span>
                <strong>{{ number_format($s['coins_spent'], 0, ',', '.') }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-bag"></i>
                <span>Acquisti</span>
                <strong>{{ $s['purchases'] }} <small class="text-muted">da {{ $s['buyers'] }} utenti</small></strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-gift"></i>
                <span>Oggetti regalati dall'admin</span>
                <strong>{{ $s['gifts'] }}</strong>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <section class="admin-card h-100">
                <div class="admin-card-header">
                    <h2 class="admin-section-title">Più comprati</h2>
                </div>
                <div class="admin-card-body">
                    @if ($s['top_items']->isEmpty())
                        <p class="admin-muted mb-0">Ancora nessun acquisto.</p>
                    @else
                        <ol class="mb-0 ps-3">
                            @foreach ($s['top_items'] as $row)
                                <li>{{ $row->name }} <span class="admin-muted">· {{ $row->total }}</span></li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="admin-card h-100">
                <div class="admin-card-header">
                    <h2 class="admin-section-title">Chi ha più monete</h2>
                </div>
                <div class="admin-card-body">
                    @if ($s['richest']->isEmpty())
                        <p class="admin-muted mb-0">Nessuno ha ancora monete.</p>
                    @else
                        <ol class="mb-0 ps-3">
                            @foreach ($s['richest'] as $u)
                                <li>
                                    <a href="{{ route('admin.avatar.user', $u) }}" class="text-decoration-none">{{ $u->nickname }}</a>
                                    <span class="admin-muted">· {{ number_format($u->coins, 0, ',', '.') }} monete</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Regala a un utente</h2>
                <p class="admin-muted mb-0">Un oggetto, delle monete o entrambi. Dopo il regalo si apre la scheda dell'utente con il suo inventario.</p>
            </div>
        </div>
        <div class="admin-card-body">
            @include('admin.avatar._gift')
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Oggetti</h2>
                <p class="admin-muted mb-0">
                    "Livello" = si riceve in regalo a quel livello. "Prezzo" = si compra nel negozio.
                    La grafica degli oggetti è nel sito: qui si cambiano nome, rarità, livello, prezzo e se è attivo.
                </p>
            </div>
        </div>

        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.avatar.index') }}" class="row g-2 align-items-center mb-3">
                <div class="col-auto">
                    <select name="slot" class="form-select" onchange="this.form.submit()">
                        <option value="all">Tutte le zone</option>
                        @foreach ($slotLabels as $key => $label)
                            <option value="{{ $key }}" @selected($slot === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="source" class="form-select" onchange="this.form.submit()">
                        <option value="all">Livelli e negozio</option>
                        <option value="level" @selected($source === 'level')>Solo regali di livello</option>
                        <option value="shop" @selected($source === 'shop')>Solo negozio</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="state" class="form-select" onchange="this.form.submit()">
                        <option value="all">Attivi e disattivati</option>
                        <option value="active" @selected($state === 'active')>Solo attivi</option>
                        <option value="inactive" @selected($state === 'inactive')>Solo disattivati</option>
                    </select>
                </div>
                <div class="col-auto flex-grow-1">
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cerca per nome o codice...">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Cerca</button>
                </div>
                @if ($search || $slot !== 'all' || $source !== 'all' || $state !== 'all')
                    <div class="col-auto">
                        <a href="{{ route('admin.avatar.index') }}" class="btn btn-outline-secondary">Azzera</a>
                    </div>
                @endif
            </form>
        </div>

        @if ($items->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                    <p class="mb-0">Nessun oggetto trovato.</p>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Oggetto</th>
                            <th>Zona</th>
                            <th>Rarità</th>
                            <th>Livello</th>
                            <th>Prezzo</th>
                            <th>Posseduto da</th>
                            <th>Acquisti</th>
                            <th>Stato</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr class="{{ $item->is_active ? '' : 'opacity-50' }}">
                                <td>
                                    <div class="fw-bold">{{ $item->name }}</div>
                                    <div class="small admin-muted"><code>{{ $item->key }}</code></div>
                                </td>
                                <td>{{ $slotLabels[$item->slot] ?? $item->slot }}</td>
                                <td><span class="badge {{ $rarityClass[$item->rarity] ?? 'bg-secondary' }}">{{ ucfirst($item->rarity) }}</span></td>
                                <td>{{ $item->unlock_level ? 'Lv '.$item->unlock_level : '-' }}</td>
                                <td>
                                    @if ($item->price !== null)
                                        <i class="bi bi-coin text-warning"></i> {{ $item->price }}
                                        @if ($item->shop_min_level > 1)
                                            <div class="small admin-muted">da Lv {{ $item->shop_min_level }}</div>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $owners[$item->id] ?? 0 }}</td>
                                <td>{{ $purchases[$item->id] ?? 0 }}</td>
                                <td>
                                    @if ($item->is_active)
                                        <span class="badge bg-success">Attivo</span>
                                    @else
                                        <span class="badge bg-secondary">Disattivato</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.avatar.edit', $item) }}" class="btn btn-sm btn-outline-primary">Modifica</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
