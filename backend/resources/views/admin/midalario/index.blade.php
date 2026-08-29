@extends('layouts.admin')

@section('title', 'Il Midalario')
@section('kicker', 'Il Midalario')
@section('page-title', 'Il Midalario')

@php
    $statusLabels = [
        'open' => ['Iscrizioni aperte', 'bg-success'],
        'closed' => ['Iscrizioni chiuse', 'bg-warning text-dark'],
        'running' => ['In corso', 'bg-primary'],
        'finished' => ['Terminato', 'bg-secondary'],
    ];
@endphp

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Quiz live Il Midalario</h2>
                <p class="admin-muted mb-0">Quiz con partecipazione libera e partenza in contemporanea per tutti.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.midalario.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i>
                    Crea quiz Midalario
                </a>
            </div>
        </div>

        @if ($quizzes->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-broadcast fs-1 d-block mb-2"></i>
                    <p class="mb-3">Nessun quiz Midalario creato.</p>
                    <a href="{{ route('admin.midalario.create') }}" class="btn btn-primary">Crea quiz Midalario</a>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Domande</th>
                            <th>Partecipanti</th>
                            <th>Stato</th>
                            <th>Sala</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quizzes as $quiz)
                            @php
                                [$label, $badgeClass] = $statusLabels[$quiz->midalario_status] ?? ['-', 'bg-secondary'];
                            @endphp
                            <tr>
                                <td>
                                    <div class="admin-title-cell">
                                        <span class="admin-thumb">
                                            @if ($quiz->image_path)
                                                <img src="{{ $quiz->image_url }}" alt="">
                                            @else
                                                <i class="bi bi-image admin-thumb-empty"></i>
                                            @endif
                                        </span>
                                        <div class="admin-title-cell-text">
                                            <div class="fw-bold">{{ $quiz->title }}</div>
                                            <div class="small admin-muted">{{ \Illuminate\Support\Str::limit($quiz->description, 80) ?: 'Nessuna descrizione' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $quiz->questions_count }}</td>
                                <td>{{ $quiz->participants_count }}</td>
                                <td>
                                    <span class="badge {{ $quiz->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $quiz->is_active ? 'Attivo' : 'Non attivo' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                                </td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-sm btn-outline-primary">
                                            Domande
                                        </a>
                                        <a href="{{ route('admin.midalario.monitor', $quiz) }}" class="btn btn-sm btn-outline-success">
                                            Sala
                                        </a>
                                        <a href="{{ route('admin.midalario.edit', $quiz) }}" class="btn btn-sm btn-warning">
                                            Modifica
                                        </a>
                                        <form action="{{ route('admin.midalario.destroy', $quiz) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Eliminare questo quiz Midalario?')">
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
