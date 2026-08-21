@extends('layouts.admin')

@section('title', 'Modifica quiz Midalario')
@section('kicker', 'Il Midalario')
@section('page-title', 'Modifica quiz Midalario')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $quiz->title }}</h2>
                <p class="admin-muted mb-0">Aggiorna titolo, descrizione e stato del quiz.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-primary btn-sm">
                    Domande
                </a>
                <a href="{{ route('admin.midalario.monitor', $quiz) }}" class="btn btn-outline-success btn-sm">
                    Sala d'attesa
                </a>
                <a href="{{ route('admin.midalario.index') }}" class="btn btn-outline-secondary btn-sm">
                    Torna a Il Midalario
                </a>
            </div>
        </div>
        <div class="admin-card-body">
            <form action="{{ route('admin.midalario.update', $quiz) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="admin-form-grid">
                    <div class="full">
                        <label class="form-label">Titolo</label>
                        <input class="form-control" name="title" value="{{ old('title', $quiz->title) }}" required>
                    </div>
                    <div class="full">
                        <label class="form-label">Descrizione</label>
                        <textarea class="form-control" name="description" rows="4">{{ old('description', $quiz->description) }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Stato</label>
                        <select class="form-select" name="is_active">
                            <option value="1" {{ old('is_active', $quiz->is_active) ? 'selected' : '' }}>Attivo (visibile agli utenti)</option>
                            <option value="0" {{ !old('is_active', $quiz->is_active) ? 'selected' : '' }}>Non attivo</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.midalario.index') }}" class="btn btn-outline-secondary">Annulla</a>
                    <button class="btn btn-primary">Aggiorna quiz</button>
                </div>
            </form>
        </div>
    </section>
@endsection
