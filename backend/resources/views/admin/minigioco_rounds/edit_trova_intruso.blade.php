@extends('layouts.admin')

@section('title', 'Modifica puzzle')
@section('kicker', 'Gestione puzzle')
@section('page-title', 'Modifica puzzle')

@section('content')
    @php
        $items = $round->items;
        $currentIntruso = $items->search(fn ($item) => $item->is_intruso);
    @endphp
    <form action="{{ route('admin.minigiochi.rounds.update', [$minigioco->id, $round->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="d-grid gap-3">
            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">{{ $minigioco->title }}</h2>
                        <p class="admin-muted mb-0">Aggiorna i 4 elementi e quale dei 4 è l'intruso.</p>
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
                                value="{{ old('time_limit_seconds', $round->time_limit_seconds) }}" min="5" required>
                        </div>
                        <div>
                            <label class="form-label">Formato elementi</label>
                            <div class="d-flex gap-3 pt-2">
                                <div class="form-check">
                                    <input type="radio" name="content_mode" value="testo" class="form-check-input content-mode-radio" id="mode_testo" {{ old('content_mode', $round->content_mode) === 'testo' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="mode_testo">Solo testo</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="content_mode" value="immagine" class="form-check-input content-mode-radio" id="mode_immagine" {{ old('content_mode', $round->content_mode) === 'immagine' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="mode_immagine">Solo immagine</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    @for ($i = 0; $i < 4; $i++)
                        @php $item = $items->get($i); @endphp
                        <div class="admin-form-grid mb-3">
                            <div class="full d-flex align-items-center gap-2">
                                <label class="form-label fw-bold mb-0">Elemento {{ $i + 1 }}</label>
                                <div class="form-check ms-auto">
                                    <input type="radio" name="intruso" value="{{ $i }}" class="form-check-input"
                                        id="intruso_{{ $i }}" {{ old('intruso', $currentIntruso) == $i ? 'checked' : '' }} required>
                                    <label class="form-check-label text-danger" for="intruso_{{ $i }}">È l'intruso</label>
                                </div>
                            </div>
                            <div class="mode-field mode-field-testo">
                                <label class="form-label">Testo (parola / didascalia)</label>
                                <input type="text" name="items[{{ $i }}][label]" class="form-control"
                                    value="{{ old("items.$i.label", $item?->label) }}">
                            </div>
                            <div class="mode-field mode-field-immagine" data-has-image="{{ $item?->image_path ? '1' : '0' }}">
                                <label class="form-label">Immagine</label>
                                @if ($item?->image_path)
                                    <img src="{{ $item->image_url }}" class="img-fluid rounded border mb-2" style="max-height: 100px;" alt="">
                                @endif
                                <input type="file" name="items[{{ $i }}][image]" class="form-control" accept=".jpg,.jpeg,.png,image/*">
                                @if ($item?->image_path)
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="items[{{ $i }}][remove_image]" value="1" class="form-check-input" id="remove_image_{{ $i }}">
                                        <label class="form-check-label text-danger" for="remove_image_{{ $i }}">Elimina immagine</label>
                                    </div>
                                @endif
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
                    Aggiorna puzzle
                </button>
            </div>
        </div>
    </form>

    @include('admin.minigioco_rounds._content_mode_script')
@endsection
