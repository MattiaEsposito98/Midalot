@extends('layouts.admin')

@section('title', 'Aggiungi domanda')
@section('kicker', 'Gestione domande')
@section('page-title', 'Aggiungi domanda')

@section('content')
    <form action="{{ route('admin.minigiochi.rounds.store', $minigioco->id) }}" method="POST">
        @csrf

        <div class="d-grid gap-3">
            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">{{ $minigioco->title }}</h2>
                        <p class="admin-muted mb-0">Inserisci la parola e imposta lo spostamento sulla tastiera.</p>
                    </div>
                    <a href="{{ route('admin.minigiochi.rounds.index', $minigioco->id) }}"
                        class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i>
                        Torna alle domande
                    </a>
                </div>
                <div class="admin-card-body">
                    <div class="admin-form-grid">
                        <div>
                            <label class="form-label">Parola</label>
                            <input type="text" name="parola" class="form-control text-uppercase"
                                value="{{ old('parola') }}" required>
                            <small class="admin-muted">Verrà salvata automaticamente in maiuscolo.</small>
                        </div>

                        <div>
                            <label class="form-label">Tempo limite (secondi)</label>
                            <input type="number" name="time_limit_seconds" class="form-control"
                                value="{{ old('time_limit_seconds', 20) }}" min="5" required>
                        </div>

                        <div>
                            <label class="form-label">Direzione dello spostamento</label>
                            <select name="direzione" class="form-select" required>
                                <option value="destra" {{ old('direzione', 'destra') === 'destra' ? 'selected' : '' }}>Destra</option>
                                <option value="sinistra" {{ old('direzione') === 'sinistra' ? 'selected' : '' }}>Sinistra</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Quantità (numero di tasti)</label>
                            <input type="number" name="quantita" class="form-control"
                                value="{{ old('quantita', 1) }}" min="1" max="9" required>
                            <small class="admin-muted">Lo spostamento avviene solo sulla stessa riga della tastiera (qwertyuiop / asdfghjkl / zxcvbnm), in modo circolare.</small>
                        </div>
                    </div>
                </div>
            </section>

            <div class="d-flex justify-content-between gap-2">
                <a href="{{ route('admin.minigiochi.rounds.index', $minigioco->id) }}" class="btn btn-outline-secondary">
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
