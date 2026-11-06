{{-- Form "Regala a un utente": oggetto e/o monete. $prefillUser opzionale. --}}
<form method="POST" action="{{ route('admin.avatar.gift') }}" class="row g-2 align-items-end">
    @csrf
    <div class="col-md-4">
        <label class="form-label small mb-1">Utente (nickname o email)</label>
        <input type="text" name="user" class="form-control" required maxlength="255"
            value="{{ old('user', $prefillUser ?? '') }}" placeholder="es. mario92">
    </div>
    <div class="col-md-4">
        <label class="form-label small mb-1">Oggetto da regalare</label>
        <select name="item" class="form-select">
            <option value="">— nessun oggetto —</option>
            @foreach ($allItems->groupBy('slot') as $slotKey => $group)
                <optgroup label="{{ $slotLabels[$slotKey] ?? $slotKey }}">
                    @foreach ($group as $it)
                        <option value="{{ $it->key }}" @selected(old('item') === $it->key)>{{ $it->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Monete (+/-)</label>
        <input type="number" name="coins" class="form-control" value="{{ old('coins') }}" min="-100000" max="100000" placeholder="es. 200">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-gift"></i> Regala</button>
    </div>
    <div class="col-12">
        <small class="admin-muted">L'utente vedrà l'avviso "nuovo oggetto" al prossimo accesso. Con un numero negativo si tolgono monete (mai sotto zero).</small>
    </div>
</form>
