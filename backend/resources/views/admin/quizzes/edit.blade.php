@extends('layouts.admin')

@section('title', 'Modifica quiz')
@section('kicker', 'Gestione quiz')
@section('page-title', 'Modifica quiz')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $quiz->title }}</h2>
                <p class="admin-muted mb-0">Aggiorna informazioni e stato del quiz.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-question-square"></i>
                    Domande
                </a>
                <a href="{{ route('admin.quizzes.users', $quiz->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-person-plus"></i>
                    Utenti
                </a>
                <a href="{{ route('admin.quizzes.leaderboard', $quiz->id) }}" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-trophy"></i>
                    Classifica
                </a>
                <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Torna ai quiz
                </a>
            </div>
        </div>

        <div class="admin-card-body">
            <form action="{{ route('admin.quizzes.update', $quiz->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="admin-form-grid">
                    <div class="full">
                        <label class="form-label">Titolo</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $quiz->title) }}" required>
                    </div>

                    <div class="full">
                        <label class="form-label">Descrizione</label>
                        <textarea name="description" class="form-control" rows="5">{{ old('description', $quiz->description) }}</textarea>
                    </div>

                    <div>
                        <label class="form-label">Stato</label>
                        <select name="is_active" class="form-select">
                            <option value="1" {{ old('is_active', $quiz->is_active) ? 'selected' : '' }}>Attivo</option>
                            <option value="0" {{ !old('is_active', $quiz->is_active) ? 'selected' : '' }}>Non attivo</option>
                        </select>
                    </div>

                    <div class="full form-check">
                        <input type="checkbox" name="restrict_to_specific_users" value="1" id="restrictUsers" class="form-check-input" {{ old('restrict_to_specific_users', $quiz->restrict_to_specific_users) ? 'checked' : '' }}>
                        <label class="form-check-label" for="restrictUsers">
                            Limita a utenti specifici (se disattivo, il quiz e' disponibile per tutti gli utenti registrati)
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary">Annulla</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Aggiorna quiz
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
