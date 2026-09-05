@extends('layouts.admin')

@section('title', 'Aggiungi puzzle')
@section('kicker', 'Gestione puzzle')
@section('page-title', 'Aggiungi puzzle')

@section('content')
    <form action="{{ route('admin.minigiochi.rounds.store', $minigioco->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="d-grid gap-3">
            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">{{ $minigioco->title }}</h2>
                        <p class="admin-muted mb-0">Inserisci 4 elementi e segna quale dei 4 è l'intruso.</p>
                    </div>
                    <a href="{{ route('admin.minigiochi.rounds.index', $minigioco->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i>
                        Torna ai puzzle
                    </a>
                </div>
                <div class="admin-card-body">
                    <div class="admin-form-grid">
                        <div>
                            <label class="form-label">Tempo limite (secondi)</label>
                            <input type="number" name="time_limit_seconds" class="form-control"
                                value="{{ old('time_limit_seconds', 20) }}" min="5" required>
                        </div>
                        <div>
                            <label class="form-label">Formato elementi</label>
                            <div class="d-flex gap-3 pt-2">
                                <div class="form-check">
                                    <input type="radio" name="content_mode" value="testo" class="form-check-input content-mode-radio" id="mode_testo" {{ old('content_mode', 'testo') === 'testo' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="mode_testo">Solo testo</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="content_mode" value="immagine" class="form-check-input content-mode-radio" id="mode_immagine" {{ old('content_mode') === 'immagine' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="mode_immagine">Solo immagine</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    @for ($i = 0; $i < 4; $i++)
                        <div class="admin-form-grid mb-3">
                            <div class="full d-flex align-items-center gap-2">
                                <label class="form-label fw-bold mb-0">Elemento {{ $i + 1 }}</label>
                                <div class="form-check ms-auto">
                                    <input type="radio" name="intruso" value="{{ $i }}" class="form-check-input"
                                        id="intruso_{{ $i }}" {{ old('intruso') == $i ? 'checked' : '' }} required>
                                    <label class="form-check-label text-danger" for="intruso_{{ $i }}">È l'intruso</label>
                                </div>
                            </div>
                            <div class="mode-field mode-field-testo">
                                <label class="form-label">Testo (parola / didascalia)</label>
                                <input type="text" name="items[{{ $i }}][label]" class="form-control"
                                    value="{{ old("items.$i.label") }}">
                            </div>
                            <div class="mode-field mode-field-immagine">
                                <label class="form-label">Immagine</label>
                                <input type="file" name="items[{{ $i }}][image]" class="form-control" accept=".jpg,.jpeg,.png,image/*">
                            </div>
                        </div>
                    @endfor
                </div>
            </section>

            <div class="d-flex justify-content-between gap-2">
                <a href="{{ route('admin.minigiochi.rounds.index', $minigioco->id) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Torna ai puzzle
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i>
                    Salva puzzle
                </button>
            </div>
        </div>
    </form>

    @include('admin.minigioco_rounds._content_mode_script')
@endsection
