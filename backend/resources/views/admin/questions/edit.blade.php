@extends('layouts.app')

@section('content')
    <div class="container py-4">

        <h2 class="mb-4">Modifica Domanda</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.quizzes.questions.update', [$quiz->id, $question->id]) }}" method="POST"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')

            <!-- ===================== -->
            <!-- CONTENUTO DOMANDA -->
            <!-- ===================== -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header fw-bold">
                    📝 Contenuto Domanda
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Testo</label>
                        <textarea name="question_text" class="form-control" rows="4" required>{{ old('question_text', $question->question_text) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- ===================== -->
            <!-- RISPOSTE -->
            <!-- ===================== -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header fw-bold">
                    🎯 Risposte
                </div>
                <div class="card-body">

                    @php
                        $answers = $question->answers()->orderBy('id')->get();
                        $correctIndex = $answers->search(fn($a) => $a->is_correct);
                    @endphp

                    @for ($i = 0; $i < 4; $i++)
                        <div class="mb-3">
                            <label class="form-label">Risposta {{ $i + 1 }}</label>
                            <input type="text" name="answers[]" class="form-control"
                                value="{{ old('answers.' . $i, $answers[$i]->answer_text ?? '') }}" required>
                        </div>
                    @endfor

                    <div class="mb-3">
                        <label class="form-label">Qual è la risposta corretta?</label>
                        <select name="correct_answer" class="form-control" required>
                            <option value="">Seleziona...</option>
                            @for ($i = 0; $i < 4; $i++)
                                <option value="{{ $i }}" @if (old('correct_answer', $correctIndex) == $i) selected @endif>
                                    Risposta {{ $i + 1 }}
                                </option>
                            @endfor
                        </select>
                    </div>

                </div>
            </div>

            <!-- ===================== -->
            <!-- MEDIA -->
            <!-- ===================== -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header fw-bold">
                    🎬 Media (opzionali)
                </div>
                <div class="card-body">

                    <div class="row">

                        <!-- IMMAGINE -->
                        <div class="col-md-4 mb-4">
                            <h6 class="fw-bold">🖼 Immagine</h6>

                            @if ($question->image_path)
                                <img src="{{ asset('storage/' . $question->image_path) }}" class="img-fluid rounded mb-2"
                                    style="max-height:200px;">
                            @else
                                <p class="text-muted small">Nessuna immagine</p>
                            @endif

                            <input type="file" name="image" class="form-control form-control-sm"
                                accept=".jpg,.jpeg,.png,image/*">

                            <small class="text-muted d-block mt-1">
                                JPG, PNG – Max 2MB
                            </small>

                            @if ($question->image_path)
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="remove_image" value="1" class="form-check-input"
                                        id="remove_image">
                                    <label class="form-check-label text-danger" for="remove_image">
                                        Elimina immagine
                                    </label>
                                </div>
                            @endif
                        </div>

                        <!-- AUDIO -->
                        <div class="col-md-4 mb-4">
                            <h6 class="fw-bold">🎧 Audio</h6>

                            @if ($question->audio_path)
                                <audio controls class="w-100 mb-2">
                                    <source src="{{ asset('storage/' . $question->audio_path) }}">
                                </audio>
                            @else
                                <p class="text-muted small">Nessun audio</p>
                            @endif

                            <input type="file" name="audio" class="form-control form-control-sm"
                                accept=".mp3,.wav,.ogg,audio/*">

                            <small class="text-muted d-block mt-1">
                                MP3, WAV, OGG – Max 5MB
                            </small>

                            @if ($question->audio_path)
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="remove_audio" value="1" class="form-check-input"
                                        id="remove_audio">
                                    <label class="form-check-label text-danger" for="remove_audio">
                                        Elimina audio
                                    </label>
                                </div>
                            @endif
                        </div>

                        <!-- VIDEO -->
                        <div class="col-md-4 mb-4">
                            <h6 class="fw-bold">🎥 Video</h6>

                            @if ($question->video_path)
                                <video controls class="w-100 mb-2" style="max-height:200px;">
                                    <source src="{{ asset('storage/' . $question->video_path) }}">
                                </video>
                            @else
                                <p class="text-muted small">Nessun video</p>
                            @endif

                            <input type="file" name="video" class="form-control form-control-sm"
                                accept=".mp4,.mov,.webm,video/*">

                            <small class="text-muted d-block mt-1">
                                MP4, MOV, WEBM – Max 20MB
                            </small>

                            @if ($question->video_path)
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="remove_video" value="1" class="form-check-input"
                                        id="remove_video">
                                    <label class="form-check-label text-danger" for="remove_video">
                                        Elimina video
                                    </label>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

            <!-- ===================== -->
            <!-- IMPOSTAZIONI -->
            <!-- ===================== -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header fw-bold">
                    ⚙️ Impostazioni
                </div>
                <div class="card-body">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Timer (secondi)</label>
                            <input type="number" name="time_limit_seconds"
                                value="{{ old('time_limit_seconds', $question->time_limit_seconds) }}" class="form-control"
                                min="5" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ordine</label>

                            <input type="number" name="order" id="orderInput"
                                value="{{ old('order', $question->order) }}" class="form-control" min="1"
                                required>

                            <small class="text-muted">
                                Numeri già utilizzati: {{ implode(', ', $usedOrders) ?: 'nessuno' }}
                            </small>
                        </div>

                        <script>
                            const usedOrders = @json($usedOrders);
                            const input = document.getElementById('orderInput');

                            input.addEventListener('input', function() {

                                const value = parseInt(this.value);

                                if (usedOrders.includes(value)) {
                                    this.setCustomValidity('Numero già utilizzato per questo quiz');
                                } else {
                                    this.setCustomValidity('');
                                }

                            });
                        </script>
                    </div>

                </div>
            </div>

            <!-- BOTTONI -->
            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-secondary">
                    ← Torna alle Domande
                </a>

                <button type="submit" class="btn btn-success px-4">
                    💾 Aggiorna
                </button>
            </div>

        </form>
    </div>
@endsection
