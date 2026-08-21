@extends('layouts.admin')

@section('title', 'Aggiungi domanda')
@section('kicker', 'Gestione domande')
@section('page-title', 'Aggiungi domanda')
@section('activeNav', match ($quiz->type) {
    'midalario' => 'midalario',
    'training' => 'training',
    default => 'quizzes',
})

@section('content')
    <form action="{{ route('admin.quizzes.questions.store', $quiz->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="d-grid gap-3">
            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">{{ $quiz->title }}</h2>
                        <p class="admin-muted mb-0">Inserisci testo, risposte, media e impostazioni della domanda.</p>
                    </div>
                    <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}"
                        class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i>
                        Torna alle domande
                    </a>
                </div>
                <div class="admin-card-body">
                    <label class="form-label">Testo domanda</label>
                    <textarea name="question_text" class="form-control" rows="4" required>{{ old('question_text') }}</textarea>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-section-title">Risposte</h2>
                </div>
                <div class="admin-card-body">
                    <div class="admin-form-grid">
                        @for ($i = 0; $i < 4; $i++)
                            <div>
                                <label class="form-label">Risposta {{ $i + 1 }}</label>
                                <input type="text" name="answers[]" class="form-control"
                                    value="{{ old('answers.' . $i) }}" required>
                            </div>
                        @endfor

                        <div class="full">
                            <label class="form-label">Risposta corretta</label>
                            <select name="correct_answer" class="form-select" required>
                                <option value="">Seleziona...</option>
                                @for ($i = 0; $i < 4; $i++)
                                    <option value="{{ $i }}" @if (old('correct_answer') == $i) selected @endif>
                                        Risposta {{ $i + 1 }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-section-title">Media opzionali</h2>
                </div>
                <div class="admin-card-body">
                    <div class="admin-form-grid">
                        <div>
                            <label class="form-label">Immagine</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,image/*">
                            <small class="admin-muted">JPG o PNG, max 2MB.</small>
                        </div>
                        <div>
                            <label class="form-label">Audio</label>
                            <input type="file" name="audio" class="form-control" accept=".mp3,.wav,.ogg,audio/*">
                            <small class="admin-muted">MP3, WAV o OGG, max 5MB.</small>
                        </div>
                        <div>
                            <label class="form-label">Video</label>
                            <input type="file" name="video" class="form-control" accept=".mp4,.mov,.webm,video/*">
                            <small class="admin-muted">MP4, MOV o WEBM, max 20MB.</small>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-section-title">Impostazioni</h2>
                </div>
                <div class="admin-card-body">
                    <div class="admin-form-grid">
                        <div>
                            <label class="form-label">Tempo limite (secondi)</label>
                            <input type="number" name="time_limit_seconds" class="form-control"
                                value="{{ old('time_limit_seconds', 10) }}" min="5" required>
                        </div>
                        <div>
                            <label class="form-label">Ordine</label>
                            <input type="number" name="order" id="orderInput" class="form-control"
                                value="{{ old('order', $nextOrder) }}" min="1" required>
                            <small class="admin-muted">Numeri gia utilizzati:
                                {{ implode(', ', $usedOrders) ?: 'nessuno' }}</small>
                        </div>
                    </div>
                </div>
            </section>

            <div class="d-flex justify-content-between gap-2">
                <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Torna alle domande
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i>
                    Salva domanda
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const usedOrders = @json($usedOrders);
        const input = document.getElementById('orderInput');

        input.addEventListener('input', function() {
            const value = parseInt(this.value);

            if (usedOrders.includes(value)) {
                this.setCustomValidity('Numero gia utilizzato per questo quiz');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
@endpush
