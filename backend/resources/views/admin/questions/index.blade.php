@extends('layouts.admin')

@section('title', 'Domande quiz')
@section('kicker', 'Gestione domande')
@section('page-title', 'Domande')
@section('activeNav', match ($quiz->type) {
    'midalario' => 'midalario',
    'training' => 'training',
    default => 'quizzes',
})

@php
    $backRoute = match ($quiz->type) {
        'midalario' => route('admin.midalario.index'),
        'training' => route('admin.training.quizzes.index'),
        default => route('admin.quizzes.index'),
    };
    $backLabel = match ($quiz->type) {
        'midalario' => "Torna a Il Midalario",
        'training' => 'Torna ai training',
        default => 'Torna ai quiz',
    };
@endphp

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $quiz->title }}</h2>
                <p class="admin-muted mb-0">Ordina e modifica le domande del quiz.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.quizzes.questions.create', $quiz->id) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i>
                    Aggiungi domanda
                </a>
                <a href="{{ $backRoute }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    {{ $backLabel }}
                </a>
            </div>
        </div>

        @if ($questions->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-question-square fs-1 d-block mb-2"></i>
                    <p class="mb-3">Nessuna domanda ancora inserita.</p>
                    <a href="{{ route('admin.quizzes.questions.create', $quiz->id) }}" class="btn btn-primary">Aggiungi domanda</a>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Ordine</th>
                            <th>Testo</th>
                            <th>Media</th>
                            <th>Timer</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($questions as $question)
                            <tr>
                                <td><span class="badge bg-primary">{{ $question->order }}</span></td>
                                <td>{{ \Illuminate\Support\Str::limit($question->question_text, 90) }}</td>
                                <td>
                                    <div class="admin-actions">
                                        @if ($question->image_path)
                                            <a href="{{ asset('storage/' . $question->image_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-image"></i>
                                                Immagine
                                            </a>
                                        @endif
                                        @if ($question->audio_path)
                                            <a href="{{ asset('storage/' . $question->audio_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-volume-up"></i>
                                                Audio
                                            </a>
                                        @endif
                                        @if ($question->video_path)
                                            <a href="{{ asset('storage/' . $question->video_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-camera-video"></i>
                                                Video
                                            </a>
                                        @endif
                                        @if (!$question->image_path && !$question->audio_path && !$question->video_path)
                                            <span class="admin-muted">Nessun media</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $question->time_limit_seconds }} sec</td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="{{ route('admin.quizzes.questions.edit', [$quiz->id, $question->id]) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                            Modifica
                                        </a>
                                        <form action="{{ route('admin.quizzes.questions.destroy', [$quiz->id, $question->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Sei sicuro di voler eliminare questa domanda?')">
                                                <i class="bi bi-trash"></i>
                                                Elimina
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
