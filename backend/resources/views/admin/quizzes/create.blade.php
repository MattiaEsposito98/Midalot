@extends('layouts.admin')

@section('title', 'Crea quiz')
@section('kicker', 'Nuovo quiz')
@section('page-title', 'Crea quiz')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Informazioni quiz</h2>
                <p class="admin-muted mb-0">Imposta titolo e descrizione. Potrai aggiungere domande e utenti dopo il salvataggio.</p>
            </div>
            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Torna ai quiz
            </a>
        </div>

        <div class="admin-card-body">
            <form action="{{ route('admin.quizzes.store') }}" method="POST">
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
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary">Annulla</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Salva quiz
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
