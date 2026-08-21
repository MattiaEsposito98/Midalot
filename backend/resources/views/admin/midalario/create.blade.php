@extends('layouts.admin')

@section('title', 'Crea quiz Midalario')
@section('kicker', 'Il Midalario')
@section('page-title', 'Crea quiz Midalario')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Nuovo quiz Midalario</h2>
                <p class="admin-muted mb-0">Dopo il salvataggio potrai aggiungere le domande e gestire la sala d'attesa.</p>
            </div>
            <a href="{{ route('admin.midalario.index') }}" class="btn btn-outline-secondary btn-sm">
                Torna a Il Midalario
            </a>
        </div>
        <div class="admin-card-body">
            <form action="{{ route('admin.midalario.store') }}" method="POST">
                @csrf
                <div class="admin-form-grid">
                    <div class="full">
                        <label class="form-label">Titolo</label>
                        <input class="form-control" name="title" value="{{ old('title') }}" required>
                    </div>
                    <div class="full">
                        <label class="form-label">Descrizione</label>
                        <textarea class="form-control" name="description" rows="4">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Stato</label>
                        <select class="form-select" name="is_active">
                            <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Attivo (visibile agli utenti)</option>
                            <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Non attivo</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.midalario.index') }}" class="btn btn-outline-secondary">Annulla</a>
                    <button class="btn btn-primary">Salva quiz</button>
                </div>
            </form>
        </div>
    </section>
@endsection
