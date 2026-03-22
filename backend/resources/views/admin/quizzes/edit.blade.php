@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Modifica Quiz</h1>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Associa utenti al quiz: {{ $quiz->title }}</h2>

            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary">
                ← Torna ai Quiz
            </a>
        </div>

        <form action="{{ route('admin.quizzes.update', $quiz->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Titolo</label>
                <input type="text" name="title" class="form-control" value="{{ $quiz->title }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descrizione</label>
                <textarea name="description" class="form-control">
{{ $quiz->description }}
            </textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Attivo</label>
                <select name="is_active" class="form-control">
                    <option value="1" {{ $quiz->is_active ? 'selected' : '' }}>Sì</option>
                    <option value="0" {{ !$quiz->is_active ? 'selected' : '' }}>No</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success">
                Aggiorna Quiz
            </button>
        </form>
    </div>
@endsection
