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
                        <p class="admin-muted mb-0">Scrivi un'affermazione e indica se è vera o falsa.</p>
                    </div>
                    <a href="{{ route('admin.minigiochi.rounds.index', $minigioco->id) }}"
                        class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i>
                        Torna alle domande
                    </a>
                </div>
                <div class="admin-card-body">
                    <div class="admin-form-grid">
                        <div class="full">
                            <label class="form-label">Affermazione</label>
                            <textarea name="affermazione" class="form-control" rows="3" required>{{ old('affermazione') }}</textarea>
                        </div>

                        <div>
                            <label class="form-label">Tempo limite (secondi)</label>
                            <input type="number" name="time_limit_seconds" class="form-control"
                                value="{{ old('time_limit_seconds', 20) }}" min="5" required>
                        </div>

                        <div>
                            <label class="form-label">Risposta corretta</label>
                            <div class="d-flex gap-3 pt-2">
                                <div class="form-check">
                                    <input type="radio" name="risposta_corretta" value="1" class="form-check-input"
                                        id="risposta_vero" {{ old('risposta_corretta') === '1' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="risposta_vero">Vero</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="risposta_corretta" value="0" class="form-check-input"
                                        id="risposta_falso" {{ old('risposta_corretta') === '0' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="risposta_falso">Falso</label>
                                </div>
                            </div>
                        </div>

                        <div class="full">
                            <label class="form-label">Spiegazione (opzionale)</label>
                            <textarea name="spiegazione" class="form-control" rows="3"
                                placeholder="Mostrata ai giocatori dopo la loro risposta">{{ old('spiegazione') }}</textarea>
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
