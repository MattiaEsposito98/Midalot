@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Aggiungi Domanda - {{ $quiz->title }}</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.quizzes.questions.store', $quiz->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label">Testo Domanda</label>
                <textarea name="question_text" class="form-control" required>{{ old('question_text') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Immagine (opzionale)
                    <small class="text-muted">
                        JPG, PNG – Max 2MB
                    </small>
                </label>
                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,image/*">
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Audio (opzionale)
                    <small class="text-muted">
                        MP3, WAV, OGG – Max 5MB
                    </small>
                </label>
                <input type="file" name="audio" class="form-control" accept=".mp3,.wav,.ogg,audio/*">
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Video (opzionale)
                    <small class="text-muted">
                        MP4, MOV, WEBM – Max 20MB
                    </small>
                </label>
                <input type="file" name="video" class="form-control" accept=".mp4,.mov,.webm,video/*">
            </div>

            <div class="mb-3">
                <label class="form-label">Tempo limite (secondi)</label>
                <input type="number" name="time_limit_seconds" class="form-control"
                    value="{{ old('time_limit_seconds', 30) }}" min="5" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Ordine</label>
                <input type="number" name="order" class="form-control" value="{{ old('order', 1) }}" min="1"
                    required>
            </div>

            <button type="submit" class="btn btn-success">
                Salva Domanda
            </button>
        </form>

        <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-secondary mt-3">
            ← Torna alle Domande
        </a>
    </div>
@endsection
