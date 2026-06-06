@extends('layouts.admin')

@section('title', 'Segnalazioni')
@section('kicker', 'Controllo contenuti')
@section('page-title', 'Segnalazioni training')

@section('content')
    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Segnalazioni domande</h2>
                <p class="admin-muted mb-0">Controlla i problemi indicati dagli utenti e aggiorna lo stato della lavorazione.</p>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="admin-page-actions">
                <a href="{{ route('admin.reports.index', ['status' => 'active']) }}"
                    class="btn btn-sm {{ $status === 'active' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Da gestire <span class="badge bg-danger ms-1">{{ $counts['active'] }}</span>
                </a>
                <a href="{{ route('admin.reports.index', ['status' => 'open']) }}"
                    class="btn btn-sm {{ $status === 'open' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Aperte <span class="badge bg-secondary ms-1">{{ $counts['open'] }}</span>
                </a>
                <a href="{{ route('admin.reports.index', ['status' => 'in_progress']) }}"
                    class="btn btn-sm {{ $status === 'in_progress' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    In lavorazione <span class="badge bg-secondary ms-1">{{ $counts['in_progress'] }}</span>
                </a>
                <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}"
                    class="btn btn-sm {{ $status === 'resolved' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Risolte <span class="badge bg-secondary ms-1">{{ $counts['resolved'] }}</span>
                </a>
                <a href="{{ route('admin.reports.index', ['status' => 'all']) }}"
                    class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Tutte
                </a>
            </div>
        </div>
    </section>

    <div class="d-grid gap-3">
        @forelse ($reports as $report)
            @php
                $statusConfig = match ($report->status) {
                    'open' => ['label' => 'Aperta', 'class' => 'bg-danger'],
                    'in_progress' => ['label' => 'In lavorazione', 'class' => 'bg-warning text-dark'],
                    default => ['label' => 'Risolta', 'class' => 'bg-success'],
                };
            @endphp

            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                            <span class="badge {{ $statusConfig['class'] }}">{{ $statusConfig['label'] }}</span>
                            <span class="small admin-muted">#{{ $report->id }} · {{ $report->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <h2 class="admin-section-title">{{ $report->quiz_title }}</h2>
                        <p class="admin-muted mb-0">{{ $report->category_name ?: 'Categoria non disponibile' }}</p>
                    </div>
                    <div class="admin-page-actions">
                        @if ($report->question)
                            <a href="{{ route('admin.quizzes.questions.edit', [$report->question->quiz_id, $report->question->id]) }}"
                                class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil-square"></i>
                                Modifica domanda
                            </a>
                        @else
                            <span class="badge bg-secondary">Domanda eliminata</span>
                        @endif
                    </div>
                </div>

                <div class="admin-card-body">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <div class="small text-uppercase fw-bold admin-muted mb-1">Domanda segnalata</div>
                                <div class="fw-bold">{{ $report->question_text }}</div>
                            </div>

                            <div class="mb-3">
                                <div class="small text-uppercase fw-bold admin-muted mb-1">Messaggio dell'utente</div>
                                <div class="border rounded p-3 bg-light" style="white-space: pre-wrap;">{{ $report->message }}</div>
                            </div>

                            <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $report->status }}">
                                <label class="form-label fw-bold" for="admin_note_{{ $report->id }}">Nota interna admin</label>
                                <textarea id="admin_note_{{ $report->id }}" name="admin_note" class="form-control mb-2"
                                    rows="3" maxlength="3000" placeholder="Annota cosa è stato verificato o corretto...">{{ $report->admin_note }}</textarea>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-save"></i>
                                    Salva nota
                                </button>
                            </form>
                        </div>

                        <div class="col-lg-4">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-uppercase fw-bold admin-muted mb-2">Segnalatore</div>
                                <div class="fw-bold">{{ $report->reporter_nickname }}</div>
                                <div class="small admin-muted mb-3">{{ $report->reporter_email ?: 'Ospite senza email' }}</div>

                                @if ($report->resolved_at)
                                    <div class="small admin-muted mb-3">
                                        Risolta il {{ $report->resolved_at->format('d/m/Y H:i') }}
                                        @if ($report->resolver)
                                            da {{ $report->resolver->name }}
                                        @endif
                                    </div>
                                @endif

                                <div class="d-grid gap-2">
                                    @if ($report->status === 'open')
                                        <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="in_progress">
                                            <button class="btn btn-outline-primary w-100">
                                                <i class="bi bi-person-check"></i>
                                                Prendi in carico
                                            </button>
                                        </form>
                                    @endif

                                    @if ($report->status !== 'resolved')
                                        <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="resolved">
                                            <button class="btn btn-success w-100">
                                                <i class="bi bi-check-circle"></i>
                                                Segna come risolta
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="open">
                                            <button class="btn btn-outline-primary w-100">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                Riapri segnalazione
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.reports.destroy', $report) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger w-100"
                                            onclick="return confirm('Eliminare definitivamente questa segnalazione?')">
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
                        <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                        Nessuna segnalazione per questo filtro.
                    </div>
                </div>
            </section>
        @endforelse
    </div>

    @if ($reports->hasPages())
        <div class="mt-3">
            {{ $reports->links() }}
        </div>
    @endif
@endsection
