@extends('layouts.app')

@section('content')
    <div class="container py-4">

        <h2 class="mb-4">Aggiungi Domanda - {{ $quiz->title }}</h2>

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

            <!-- ===================== -->
            <!-- CONTENUTO DOMANDA -->
            <!-- ===================== -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header fw-bold">
                    📝 Contenuto Domanda
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">Testo Domanda</label>
                        <textarea name="question_text" class="form-control" rows="4" required>{{ old('question_text') }}</textarea>
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

                    @for ($i = 0; $i < 4; $i++)
                        <div class="mb-3">
                            <label class="form-label">Risposta {{ $i + 1 }}</label>
                            <input type="text" name="answers[]" class="form-control" value="{{ old('answers.' . $i) }}"
                                required>
                        </div>
                    @endfor

                    <div class="mb-3">
                        <label class="form-label">Qual è la risposta corretta?</label>
                        <select name="correct_answer" class="form-control" required>
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

                            <input type="file" name="image" class="form-control form-control-sm"
                                accept=".jpg,.jpeg,.png,image/*">

                            <small class="text-muted d-block mt-1">
                                JPG, PNG – Max 2MB
                            </small>
                        </div>

                        <!-- AUDIO -->
                        <div class="col-md-4 mb-4">
                            <h6 class="fw-bold">🎧 Audio</h6>

                            <input type="file" name="audio" class="form-control form-control-sm"
                                accept=".mp3,.wav,.ogg,audio/*">

                            <small class="text-muted d-block mt-1">
                                MP3, WAV, OGG – Max 5MB
                            </small>
                        </div>

                        <!-- VIDEO -->
                        <div class="col-md-4 mb-4">
                            <h6 class="fw-bold">🎥 Video</h6>

                            <input type="file" name="video" class="form-control form-control-sm"
                                accept=".mp4,.mov,.webm,video/*">

                            <small class="text-muted d-block mt-1">
                                MP4, MOV, WEBM – Max 20MB
                            </small>
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

                        <!-- TIMER -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tempo limite (secondi)</label>
                            <input type="number" name="time_limit_seconds" class="form-control"
                                value="{{ old('time_limit_seconds', 30) }}" min="5" required>
                        </div>

                        <!-- ORDINE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ordine</label>
                            <input type="number" name="order" id="orderInput" class="form-control"
                                value="{{ old('order', $nextOrder) }}" min="1" required>

                            <small class="text-muted d-block mt-1">
                                Numeri già utilizzati: {{ implode(', ', $usedOrders) ?: 'nessuno' }}
                            </small>
                        </div>

                    </div>

                </div>
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

            <!-- BOTTONI -->
            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-secondary">
                    ← Torna alle Domande
                </a>

                <button type="submit" class="btn btn-success px-4">
                    💾 Salva Domanda
                </button>
            </div>

        </form>
    </div>
@endsection
