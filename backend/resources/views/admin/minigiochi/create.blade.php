@extends('layouts.admin')

@section('title', 'Crea minigioco')
@section('kicker', 'Nuovo minigioco')
@section('page-title', 'Crea minigioco')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Informazioni minigioco</h2>
                <p class="admin-muted mb-0">Imposta titolo e descrizione. Potrai aggiungere le domande dopo il salvataggio.</p>
            </div>
            <a href="{{ route('admin.minigiochi.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Torna ai minigiochi
            </a>
        </div>

        <div class="admin-card-body">
            <form action="{{ route('admin.minigiochi.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="admin-form-grid">
                    <div class="full">
                        <label class="form-label">Titolo</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                    </div>

                    <div class="full">
                        <label class="form-label">Descrizione</label>
                        <textarea name="description" class="form-control" rows="5">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label class="form-label">Tipo di minigioco</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Seleziona un tipo...</option>
                            <option value="tastiera_rotta" {{ old('tipo') === 'tastiera_rotta' ? 'selected' : '' }}>Tastiera Rotta</option>
                            <option value="salto_temporale" {{ old('tipo') === 'salto_temporale' ? 'selected' : '' }}>Salto Temporale</option>
                            <option value="trova_intruso" {{ old('tipo') === 'trova_intruso' ? 'selected' : '' }}>Trova l'Intruso</option>
                        </select>
                        <small class="admin-muted">Non sarà più modificabile dopo il salvataggio: determina il tipo di domande da inserire.</small>
                    </div>

                    <div>
                        <label class="form-label">Punteggio massimo</label>
                        <input type="number" name="max_score" class="form-control" value="{{ old('max_score', 30) }}" min="1" required>
                        <small class="admin-muted">Punti massimi ottenibili in totale su un tentativo completo (default 30).</small>
                    </div>

                    <div class="full">
                        <label class="form-label">Immagine di copertina (opzionale)</label>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,image/*">
                        <small class="admin-muted">JPG o PNG, max 2MB. Se non caricata, il minigioco appare senza immagine come oggi.</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.minigiochi.index') }}" class="btn btn-outline-secondary">Annulla</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Salva minigioco
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
