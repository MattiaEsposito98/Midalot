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
