@extends('layouts.admin')

@section('title', $user->name)
@section('kicker', 'Utenti')
@section('page-title', $user->name)

@section('content')
    <div class="admin-page-actions mb-3">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            Torna all'elenco
        </a>
    </div>

    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Anagrafica</h2>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted mb-1">Nome</div>
                    <div>{{ $user->name }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted mb-1">Nickname</div>
                    <div>{{ $user->nickname }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted mb-1">Email</div>
                    <div>{{ $user->email }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted mb-1">Telefono</div>
                    <div>{{ $user->phone ?: '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted mb-1">Citta</div>
                    <div>{{ $user->city?->name ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted mb-1">Data di nascita</div>
                    <div>{{ $user->birth_date?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted mb-1">Registrato il</div>
                    <div>{{ $user->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-uppercase fw-bold admin-muted mb-1">Email verificata</div>
                    <div>
                        @if ($user->email_verified_at)
                            <span class="badge bg-success">Si</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="admin-stat-grid">
        <div class="admin-stat-card">
            <i class="bi bi-ui-checks-grid"></i>
            <span>Quiz assegnati</span>
            <strong>{{ $stats['assigned_quizzes'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <i class="bi bi-flag"></i>
            <span>Tentativi quiz</span>
            <strong>{{ $stats['quiz_completed'] }}/{{ $stats['quiz_attempts'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <i class="bi bi-graph-up"></i>
            <span>Media punteggio quiz</span>
            <strong>{{ $stats['quiz_avg_score'] ?? '-' }}</strong>
        </div>
        <div class="admin-stat-card">
            <i class="bi bi-lightning-charge"></i>
            <span>Tentativi training</span>
            <strong>{{ $stats['training_completed'] }}/{{ $stats['training_attempts'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Media punteggio training</span>
            <strong>{{ $stats['training_avg_score'] ?? '-' }}</strong>
        </div>
        <div class="admin-stat-card">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Login totali</span>
            <strong>{{ $stats['logins_count'] }}</strong>
        </div>
        <div class="admin-stat-card">
            <i class="bi bi-clock-history"></i>
            <span>Ultimo accesso</span>
            <strong style="font-size: 1.1rem;">{{ $stats['last_login']?->format('d/m/Y H:i') ?? '-' }}</strong>
        </div>
    </div>

    <div class="admin-dashboard-grid">
        <section class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-section-title">Tentativi quiz</h2>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Stato</th>
                            <th>Punteggio</th>
                            <th>Tempo</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quizAttempts as $attempt)
                            <tr>
                                <td>{{ $attempt->quiz?->title ?? 'Quiz eliminato' }}</td>
                                <td>
                                    @if ($attempt->completed)
                                        <span class="badge bg-success">Completato</span>
                                    @else
                                        <span class="badge bg-secondary">In corso</span>
                                    @endif
                                </td>
                                <td>{{ $attempt->score ?? '-' }}</td>
                                <td>{{ $attempt->total_time ? gmdate('i:s', $attempt->total_time) : '-' }}</td>
                                <td>{{ $attempt->started_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="admin-empty">Nessun tentativo di quiz.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-section-title">Tentativi training</h2>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Training</th>
                            <th>Categoria</th>
                            <th>Stato</th>
                            <th>Risposte corrette</th>
                            <th>Punteggio</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trainingAttempts as $attempt)
                            <tr>
                                <td>{{ $attempt->quiz?->title ?? 'Training eliminato' }}</td>
                                <td>{{ $attempt->category?->name ?? '-' }}</td>
                                <td>
                                    @if ($attempt->completed)
                                        <span class="badge bg-success">Completato</span>
                                    @else
                                        <span class="badge bg-secondary">In corso</span>
                                    @endif
                                </td>
                                <td>{{ $attempt->correct_answers }}/{{ $attempt->total_questions }}</td>
                                <td>{{ $attempt->score ?? '-' }}</td>
                                <td>{{ $attempt->started_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="admin-empty">Nessun tentativo di training.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="admin-card mt-3">
        <div class="admin-card-header">
            <h2 class="admin-section-title">Ultimi login</h2>
        </div>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>IP</th>
                        <th>Dispositivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logins as $login)
                        <tr>
                            <td>{{ $login->logged_in_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>{{ $login->ip_address ?? '-' }}</td>
                            <td class="small admin-muted">{{ \Illuminate\Support\Str::limit($login->user_agent, 60) ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="admin-empty">Nessun login registrato.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
