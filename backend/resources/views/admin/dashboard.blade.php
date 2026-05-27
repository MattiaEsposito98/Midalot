@extends('layouts.admin')

@section('title', 'Dashboard')
@section('kicker', 'Panoramica')
@section('page-title', 'Dashboard admin')

@section('content')
    <div class="admin-stat-grid">
        <div class="admin-stat-card">
            <i class="bi bi-people"></i>
            <span>Utenti normali</span>
            <strong>{{ $stats['users'] }}</strong>
        </div>
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

    <div class="admin-dashboard-grid">
        <section class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-section-title">Quiz recenti</h2>
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
