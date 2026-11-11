@extends('layouts.admin')

@section('title', 'Modifica oggetto')
@section('kicker', 'Avatar e negozio')
@section('page-title', $item->name)

@section('content')
    <a href="{{ route('admin.avatar.index') }}" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Tutti gli oggetti</a>

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $item->name }} <small class="admin-muted"><code>{{ $item->key }}</code></small></h2>
                <p class="admin-muted mb-0">
                    Zona: {{ $slotLabels[$item->slot] ?? $item->slot }} · posseduto da {{ $owners }} utenti · comprato {{ $purchases }} volte
                </p>
            </div>
        </div>

        <div class="admin-card-body">
            <form method="POST" action="{{ route('admin.avatar.update', $item) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label">Nome</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $item->name) }}" required maxlength="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rarità</label>
                    <select name="rarity" class="form-select">
                        @foreach ($rarities as $r)
                            <option value="{{ $r }}" @selected(old('rarity', $item->rarity) === $r)>{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordine</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Regalo al livello</label>
                    <input type="number" name="unlock_level" class="form-control" min="1" max="500"
                        value="{{ old('unlock_level', $item->unlock_level) }}" placeholder="vuoto = non è un regalo">
                    <div class="form-text">Chi ha già quel livello lo riceve al prossimo accesso.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prezzo nel negozio (monete)</label>
                    <input type="number" name="price" class="form-control" min="0" max="1000000"
                        value="{{ old('price', $item->price) }}" placeholder="vuoto = non in vendita (esclusivo: {{ $defaultPrices[$item->rarity] ?? 150 }})">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Livello minimo per comprarlo</label>
                    <input type="number" name="shop_min_level" class="form-control" min="1" max="500"
                        value="{{ old('shop_min_level', $item->shop_min_level) }}" required>
                </div>

                <div class="col-12">
                    <div class="border rounded p-3" style="background:#fffdf2">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_exclusive" value="1" id="is_exclusive" class="form-check-input" @checked(old('is_exclusive', $item->is_exclusive))>
                            <label for="is_exclusive" class="form-check-label fw-bold">Oggetto esclusivo</label>
                        </div>
                        <div class="form-text mb-2">
                            Un esclusivo <b>non compare</b> nel negozio né nella vetrina (gli utenti non sanno che esiste)
                            finché non lo apri qui sotto. Chi lo compra lo tiene per sempre.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="exclusive_open" value="1" id="exclusive_open" class="form-check-input" @checked(old('exclusive_open', $item->exclusive_open))>
                                    <label for="exclusive_open" class="form-check-label">In vendita adesso</label>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label mb-1">Scadenza (opzionale)</label>
                                <input type="datetime-local" name="exclusive_until" class="form-control"
                                    value="{{ old('exclusive_until', $item->exclusive_until?->format('Y-m-d\TH:i')) }}">
                                <div class="form-text">Passata la scadenza l'oggetto sparisce dal negozio da solo. Vuoto = resta finché non lo chiudi tu.</div>
                            </div>
                        </div>
                        <div class="form-text mt-2">
                            Prezzo: se lo lasci vuoto si usa quello proposto per la rarità
                            ({{ collect($defaultPrices)->map(fn ($p, $r) => ucfirst($r).' '.$p)->implode(' · ') }}).
                            Gli esclusivi non hanno mai lo sconto della vetrina.
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', $item->is_active))>
                        <label for="is_active" class="form-check-label">Attivo</label>
                    </div>
                    <div class="form-text">
                        Un oggetto disattivato sparisce da negozio e guardaroba e non si può più indossare
                        (chi lo indossava torna al vestito base). Chi lo possiede lo ritrova se lo riattivi.
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </section>
@endsection
