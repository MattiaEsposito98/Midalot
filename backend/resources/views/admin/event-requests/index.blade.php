@extends('layouts.admin')

@section('title', 'Richieste eventi')
@section('kicker', 'Quiz personalizzati')
@section('page-title', 'Richieste "Crea il tuo evento"')

@section('content')
    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Richieste di preventivo</h2>
                <p class="admin-muted mb-0">Lead ricevuti dal form "Crea il tuo evento": rispondi al contatto indicato e aggiorna lo stato qui.</p>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="admin-page-actions">
                <a href="{{ route('admin.event-requests.index', ['status' => 'active']) }}"
                    class="btn btn-sm {{ $status === 'active' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Da gestire <span class="badge bg-danger ms-1">{{ $counts['active'] }}</span>
                </a>
                <a href="{{ route('admin.event-requests.index', ['status' => 'new']) }}"
                    class="btn btn-sm {{ $status === 'new' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Nuove <span class="badge bg-secondary ms-1">{{ $counts['new'] }}</span>
                </a>
                <a href="{{ route('admin.event-requests.index', ['status' => 'contacted']) }}"
                    class="btn btn-sm {{ $status === 'contacted' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Contattate <span class="badge bg-secondary ms-1">{{ $counts['contacted'] }}</span>
                </a>
                <a href="{{ route('admin.event-requests.index', ['status' => 'closed']) }}"
                    class="btn btn-sm {{ $status === 'closed' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Chiuse <span class="badge bg-secondary ms-1">{{ $counts['closed'] }}</span>
                </a>
                <a href="{{ route('admin.event-requests.index', ['status' => 'all']) }}"
                    class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Tutte
                </a>
            </div>
        </div>
    </section>

    <div class="d-grid gap-3">
        @forelse ($eventRequests as $eventRequest)
            @php
                $statusConfig = match ($eventRequest->status) {
                    'new' => ['label' => 'Nuova', 'class' => 'bg-danger'],
                    'contacted' => ['label' => 'Contattata', 'class' => 'bg-warning text-dark'],
                    default => ['label' => 'Chiusa', 'class' => 'bg-success'],
                };
            @endphp

            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                            <span class="badge {{ $statusConfig['class'] }}">{{ $statusConfig['label'] }}</span>
                            <span class="small admin-muted">#{{ $eventRequest->id }} · {{ $eventRequest->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <h2 class="admin-section-title">{{ $eventRequest->name }}</h2>
                        <p class="admin-muted mb-0">
                            {{ $eventRequest->event_type ?: 'Tipo evento non specificato' }}
                            @if ($eventRequest->event_date)
                                · {{ $eventRequest->event_date->format('d/m/Y') }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="admin-card-body">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <div class="small text-uppercase fw-bold admin-muted mb-1">Idea per il quiz</div>
                                <div class="border rounded p-3 bg-light" style="white-space: pre-wrap;">{{ $eventRequest->message }}</div>
                            </div>

                            <form method="POST" action="{{ route('admin.event-requests.update', $eventRequest) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $eventRequest->status }}">
                                <label class="form-label fw-bold" for="admin_note_{{ $eventRequest->id }}">Nota interna admin</label>
                                <textarea id="admin_note_{{ $eventRequest->id }}" name="admin_note" class="form-control mb-2"
                                    rows="3" maxlength="3000" placeholder="Annota preventivo inviato, esito della chiamata...">{{ $eventRequest->admin_note }}</textarea>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-save"></i>
                                    Salva nota
                                </button>
                            </form>
                        </div>

                        <div class="col-lg-4">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-uppercase fw-bold admin-muted mb-2">Contatto</div>
                                <div class="fw-bold">{{ $eventRequest->contact }}</div>
                                <div class="small admin-muted mb-3">
                                    {{ $eventRequest->user ? 'Utente registrato: ' . $eventRequest->user->nickname : 'Richiesta da ospite' }}
                                </div>

                                @if ($eventRequest->handled_at)
                                    <div class="small admin-muted mb-3">
                                        Gestita il {{ $eventRequest->handled_at->format('d/m/Y H:i') }}
                                        @if ($eventRequest->handler)
                                            da {{ $eventRequest->handler->name }}
                                        @endif
                                    </div>
                                @endif

                                <div class="d-grid gap-2">
                                    @if ($eventRequest->status === 'new')
                                        <form method="POST" action="{{ route('admin.event-requests.update', $eventRequest) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="contacted">
                                            <button class="btn btn-outline-primary w-100">
                                                <i class="bi bi-telephone-outbound"></i>
                                                Segna come contattata
                                            </button>
                                        </form>
                                    @endif

                                    @if ($eventRequest->status !== 'closed')
                                        <form method="POST" action="{{ route('admin.event-requests.update', $eventRequest) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="closed">
                                            <button class="btn btn-success w-100">
                                                <i class="bi bi-check-circle"></i>
                                                Segna come chiusa
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.event-requests.update', $eventRequest) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="new">
                                            <button class="btn btn-outline-primary w-100">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                Riapri richiesta
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.event-requests.destroy', $eventRequest) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger w-100"
                                            onclick="return confirm('Eliminare definitivamente questa richiesta?')">
                                            <i class="bi bi-trash"></i>
                                            Elimina
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @empty
            <section class="admin-card">
                <div class="admin-empty">
                    <div>
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Nessuna richiesta per questo filtro.
                    </div>
                </div>
            </section>
        @endforelse
    </div>

    @if ($eventRequests->hasPages())
        <div class="mt-3">
            {{ $eventRequests->links() }}
        </div>
    @endif
@endsection
