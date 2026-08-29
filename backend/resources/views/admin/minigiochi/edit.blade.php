@extends('layouts.admin')

@section('title', 'Modifica minigioco')
@section('kicker', 'Gestione minigiochi')
@section('page-title', 'Modifica minigioco')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $minigioco->title }}</h2>
                <p class="admin-muted mb-0">Aggiorna informazioni e stato del minigioco.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.minigiochi.rounds.index', $minigioco->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-question-square"></i>
                    Domande
                </a>
                <a href="{{ route('admin.minigiochi.leaderboard', $minigioco->id) }}" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-trophy"></i>
                    Classifica
                </a>
                <a href="{{ route('admin.minigiochi.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Torna ai minigiochi
                </a>
            </div>
        </div>

        <div class="admin-card-body">
            <form action="{{ route('admin.minigiochi.update', $minigioco->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="admin-form-grid">
                    <div class="full">
                        <label class="form-label">Titolo</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $minigioco->title) }}" required>
                    </div>

                    <div class="full">
                        <label class="form-label">Descrizione</label>
                        <textarea name="description" class="form-control" rows="5">{{ old('description', $minigioco->description) }}</textarea>
                    </div>

                    <div>
                        <label class="form-label">Stato</label>
                        <select name="is_active" class="form-select">
                            <option value="1" {{ old('is_active', $minigioco->is_active) ? 'selected' : '' }}>Attivo</option>
                            <option value="0" {{ !old('is_active', $minigioco->is_active) ? 'selected' : '' }}>Non attivo</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.minigiochi.index') }}" class="btn btn-outline-secondary">Annulla</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Aggiorna minigioco
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
