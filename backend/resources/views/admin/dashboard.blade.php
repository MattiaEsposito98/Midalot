@extends('layouts.admin')

@section('title', 'Dashboard')
@section('kicker', 'Panoramica')
@section('page-title', 'Dashboard admin')

@section('content')
    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Utenti &amp; accessi</h2>
        <div class="admin-stat-grid">
            <a href="{{ route('admin.users.index') }}" class="admin-stat-card admin-stat-card-link">
                <i class="bi bi-people"></i>
                <span>Utenti</span>
                <strong>{{ $stats['users'] }}</strong>
            </a>
            <div class="admin-stat-card">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Login oggi</span>
                <strong>{{ $stats['logins_today'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-calendar-week"></i>
                <span>Login 7 giorni</span>
                <strong>{{ $stats['logins_week'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-geo-alt"></i>
                <span>Citta raggiunte</span>
                <strong>{{ $stats['cities'] }}</strong>
            </div>
        </div>
    </div>

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Quiz assegnati</h2>
        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                <i class="bi bi-ui-checks-grid"></i>
                <span>Quiz totali</span>
                <strong>{{ $stats['quizzes'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-check-circle"></i>
                <span>Quiz attivi</span>
                <strong>{{ $stats['active_quizzes'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-question-circle"></i>
                <span>Domande</span>
                <strong>{{ $stats['questions'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-flag"></i>
                <span>Tentativi completati</span>
                <strong>{{ $stats['completed_attempts'] }}/{{ $stats['attempts'] }}</strong>
            </div>
        </div>
    </div>

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Training</h2>
        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                <i class="bi bi-lightning-charge"></i>
                <span>Training totali</span>
                <strong>{{ $stats['trainings'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-lightning-charge-fill"></i>
                <span>Training attivi</span>
                <strong>{{ $stats['active_trainings'] }}</strong>
            </div>
            <a href="{{ route('admin.training.categories.index') }}" class="admin-stat-card admin-stat-card-link">
                <i class="bi bi-tags"></i>
                <span>Categorie</span>
                <strong>{{ $stats['training_categories'] }}</strong>
            </a>
            <a href="{{ route('admin.training.categories.index') }}" class="admin-stat-card admin-stat-card-link">
                <i class="bi bi-tags-fill"></i>
                <span>Sottocategorie</span>
                <strong>{{ $stats['training_subcategories'] }}</strong>
            </a>
        </div>
    </div>

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Midalario</h2>
        <div class="admin-stat-grid">
            <a href="{{ route('admin.midalario.index') }}" class="admin-stat-card admin-stat-card-link">
                <i class="bi bi-broadcast"></i>
                <span>Midalario totali</span>
                <strong>{{ $stats['midalarios'] }}</strong>
            </a>
            <div class="admin-stat-card">
                <i class="bi bi-broadcast-pin"></i>
                <span>Midalario in corso/aperti</span>
                <strong>{{ $stats['midalarios_live'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-person-check"></i>
                <span>Partecipanti Midalario</span>
                <strong>{{ $stats['midalario_participants'] }}</strong>
            </div>
        </div>
    </div>

    <div class="admin-stat-group">
        <h2 class="admin-stat-group-title">Minigiochi</h2>
        <div class="admin-stat-grid">
            <a href="{{ route('admin.minigiochi.index') }}" class="admin-stat-card admin-stat-card-link">
                <i class="bi bi-joystick"></i>
                <span>Minigiochi totali</span>
                <strong>{{ $stats['minigiochi'] }}</strong>
            </a>
            <div class="admin-stat-card">
                <i class="bi bi-controller"></i>
                <span>Minigiochi attivi</span>
                <strong>{{ $stats['active_minigiochi'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-people-fill"></i>
                <span>Giocatori minigiochi</span>
                <strong>{{ $stats['minigioco_players'] }}</strong>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-flag-fill"></i>
                <span>Partite minigiochi</span>
                <strong>{{ $stats['minigioco_completed_attempts'] }}/{{ $stats['minigioco_attempts'] }}</strong>
            </div>
        </div>
    </div>

    @php
        $midalarioStatusLabels = [
            'open' => ['Iscrizioni aperte', 'bg-success'],
            'closed' => ['Iscrizioni chiuse', 'bg-warning text-dark'],
            'running' => ['In corso', 'bg-primary'],
            'finished' => ['Terminato', 'bg-secondary'],
        ];
    @endphp

    <div class="admin-dashboard-grid">
        <div class="d-grid gap-3">
            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">Attivita recenti</h2>
                        <p class="admin-muted mb-0">Apri una sezione per vedere gli ultimi elementi creati.</p>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="accordion" id="recentActivityAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-recent-quizzes">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-recent-quizzes">
                                    <i class="bi bi-ui-checks-grid me-2"></i>
                                    Quiz one shot recenti
                                </button>
                            </h2>
                            <div id="collapse-recent-quizzes" class="accordion-collapse collapse show" data-bs-parent="#recentActivityAccordion">
                                <div class="accordion-body">
                                    <div class="d-flex justify-content-end mb-2">
                                        <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-lg"></i>
                                            Crea quiz
                                        </a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table admin-table">
                                            <thead>
                                                <tr>
                                                    <th>Quiz</th>
                                                    <th>Stato</th>
                                                    <th>Domande</th>
                                                    <th>Utenti</th>
                                                    <th>Tentativi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($recentQuizzes as $quiz)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="fw-bold text-decoration-none">
                                                                {{ $quiz->title }}
                                                            </a>
                                                            <div class="small admin-muted">{{ $quiz->created_at->format('d/m/Y H:i') }}</div>
                                                        </td>
                                                        <td>
                                                            @if ($quiz->is_active)
                                                                <span class="badge bg-success">Attivo</span>
                                                            @else
                                                                <span class="badge bg-secondary">Non attivo</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $quiz->questions_count }}</td>
                                                        <td>{{ $quiz->users_count }}</td>
                                                        <td>{{ $quiz->attempts_count }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5">
                                                            <div class="admin-empty">Nessun quiz creato.</div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-recent-trainings">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-recent-trainings">
                                    <i class="bi bi-lightning-charge me-2"></i>
                                    Training recenti
                                </button>
                            </h2>
                            <div id="collapse-recent-trainings" class="accordion-collapse collapse" data-bs-parent="#recentActivityAccordion">
                                <div class="accordion-body">
                                    <div class="d-flex justify-content-end gap-2 mb-2">
                                        <a href="{{ route('admin.training.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                                            Categorie
                                        </a>
                                        <a href="{{ route('admin.training.quizzes.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-lg"></i>
                                            Crea training
                                        </a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table admin-table">
                                            <thead>
                                                <tr>
                                                    <th>Training</th>
                                                    <th>Stato</th>
                                                    <th>Domande</th>
                                                    <th>Tentativi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($recentTrainings as $quiz)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('admin.training.quizzes.edit', $quiz) }}" class="fw-bold text-decoration-none">
                                                                {{ $quiz->title }}
                                                            </a>
                                                            <div class="small admin-muted">{{ $quiz->created_at->format('d/m/Y H:i') }}</div>
                                                        </td>
                                                        <td>
                                                            @if ($quiz->is_active)
                                                                <span class="badge bg-success">Attivo</span>
                                                            @else
                                                                <span class="badge bg-secondary">Non attivo</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $quiz->questions_count }}</td>
                                                        <td>{{ $quiz->attempts_count }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4">
                                                            <div class="admin-empty">Nessun training creato.</div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-recent-midalario">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-recent-midalario">
                                    <i class="bi bi-broadcast me-2"></i>
                                    Midalario recenti
                                </button>
                            </h2>
                            <div id="collapse-recent-midalario" class="accordion-collapse collapse" data-bs-parent="#recentActivityAccordion">
                                <div class="accordion-body">
                                    <div class="d-flex justify-content-end mb-2">
                                        <a href="{{ route('admin.midalario.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-lg"></i>
                                            Crea Midalario
                                        </a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table admin-table">
                                            <thead>
                                                <tr>
                                                    <th>Midalario</th>
                                                    <th>Stato</th>
                                                    <th>Domande</th>
                                                    <th>Partecipanti</th>
                                                    <th>Tentativi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($recentMidalarios as $quiz)
                                                    @php
                                                        [$midalarioLabel, $midalarioBadgeClass] = $midalarioStatusLabels[$quiz->midalario_status] ?? ['-', 'bg-secondary'];
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('admin.midalario.monitor', $quiz) }}" class="fw-bold text-decoration-none">
                                                                {{ $quiz->title }}
                                                            </a>
                                                            <div class="small admin-muted">{{ $quiz->created_at->format('d/m/Y H:i') }}</div>
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $midalarioBadgeClass }}">{{ $midalarioLabel }}</span>
                                                        </td>
                                                        <td>{{ $quiz->questions_count }}</td>
                                                        <td>{{ $quiz->participants_count }}</td>
                                                        <td>{{ $quiz->attempts_count }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5">
                                                            <div class="admin-empty">Nessuna sessione Midalario creata.</div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-recent-minigiochi">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-recent-minigiochi">
                                    <i class="bi bi-joystick me-2"></i>
                                    Minigiochi recenti
                                </button>
                            </h2>
                            <div id="collapse-recent-minigiochi" class="accordion-collapse collapse" data-bs-parent="#recentActivityAccordion">
                                <div class="accordion-body">
                                    <div class="d-flex justify-content-end mb-2">
                                        <a href="{{ route('admin.minigiochi.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-lg"></i>
                                            Crea minigioco
                                        </a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table admin-table">
                                            <thead>
                                                <tr>
                                                    <th>Minigioco</th>
                                                    <th>Stato</th>
                                                    <th>Domande</th>
                                                    <th>Tentativi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($recentMinigiochi as $minigioco)
                                                    <tr>
                                                        <td>
                                                            <div class="admin-title-cell">
                                                                <span class="admin-thumb">
                                                                    @if ($minigioco->image_path)
                                                                        <img src="{{ $minigioco->image_url }}" alt="">
                                                                    @else
                                                                        <i class="bi bi-image admin-thumb-empty"></i>
                                                                    @endif
                                                                </span>
                                                                <div class="admin-title-cell-text">
                                                                    <a href="{{ route('admin.minigiochi.edit', $minigioco->id) }}" class="fw-bold text-decoration-none">
                                                                        {{ $minigioco->title }}
                                                                    </a>
                                                                    <div class="small admin-muted">{{ $minigioco->created_at->format('d/m/Y H:i') }}</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            @if ($minigioco->is_active)
                                                                <span class="badge bg-success">Attivo</span>
                                                            @else
                                                                <span class="badge bg-secondary">Non attivo</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $minigioco->rounds_count }}</td>
                                                        <td>{{ $minigioco->attempts_count }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4">
                                                            <div class="admin-empty">Nessun minigioco creato.</div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="d-grid gap-3">
            <section class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-section-title">Ultimi login</h2>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($latestLogins as $login)
                        <div class="list-group-item">
                            <div class="fw-bold">
                                @if ($login->user)
                                    <x-nickname-badge :user="$login->user" />
                                @else
                                    Utente eliminato
                                @endif
                            </div>
                            <div class="small admin-muted">
                                {{ optional($login->logged_in_at)->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @empty
                        <div class="admin-empty">Nessun login registrato.</div>
                    @endforelse
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-section-title">Citta principali</h2>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($topCities as $city)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $city->name }}</span>
                            <span class="badge bg-primary">{{ $city->users_count }}</span>
                        </div>
                    @empty
                        <div class="admin-empty">Nessuna citta associata agli utenti.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
