@extends('layouts.admin')

@section('title', 'Dashboard')
@section('kicker', 'Panoramica')
@section('page-title', 'Dashboard admin')

@section('content')
    <div class="admin-stat-grid">
        <a href="{{ route('admin.users.index') }}" class="admin-stat-card admin-stat-card-link">
            <i class="bi bi-people"></i>
            <span>Utenti</span>
            <strong>{{ $stats['users'] }}</strong>
        </a>
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
            <i class="bi bi-lightning-charge"></i>
            <span>Training totali</span>
            <strong>{{ $stats['trainings'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>Training attivi</span>
            <strong>{{ $stats['active_trainings'] }}</strong>
        </div>
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
        <div class="admin-stat-card">
            <i class="bi bi-question-circle"></i>
            <span>Domande</span>
            <strong>{{ $stats['questions'] }}</strong>
        </div>
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
            <i class="bi bi-flag"></i>
            <span>Tentativi completati</span>
            <strong>{{ $stats['completed_attempts'] }}/{{ $stats['attempts'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <i class="bi bi-geo-alt"></i>
            <span>Citta raggiunte</span>
            <strong>{{ $stats['cities'] }}</strong>
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
                        <h2 class="admin-section-title">Quiz one shot recenti</h2>
                        <p class="admin-muted mb-0">Ultimi quiz creati e stato operativo.</p>
                    </div>
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
            </section>

            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">Training recenti</h2>
                        <p class="admin-muted mb-0">Ultimi training creati e stato operativo.</p>
                    </div>
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
            </section>

            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">Midalario recenti</h2>
                        <p class="admin-muted mb-0">Ultime sessioni Midalario e stato operativo.</p>
                    </div>
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
            </section>

            <section class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">Minigiochi recenti</h2>
                        <p class="admin-muted mb-0">Ultimi minigiochi creati e stato operativo.</p>
                    </div>
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
                            <div class="fw-bold">{{ $login->user->nickname ?? ($login->user->name ?? 'Utente eliminato') }}</div>
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
