@extends('layouts.admin')

@section('title', 'Minigiochi')
@section('kicker', 'Gestione contenuti')
@section('page-title', 'Minigiochi')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Elenco minigiochi</h2>
                <p class="admin-muted mb-0">Crea, modifica e consulta le classifiche dei minigiochi.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
                <a href="{{ route('admin.minigiochi.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i>
                    Crea minigioco
                </a>
            </div>
        </div>

        @if ($minigiochi->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-joystick fs-1 d-block mb-2"></i>
                    <p class="mb-3">Nessun minigioco creato.</p>
                    <a href="{{ route('admin.minigiochi.create') }}" class="btn btn-primary">Crea il primo minigioco</a>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Minigioco</th>
                            <th>Stato</th>
                            <th>Domande</th>
                            <th>Tentativi</th>
                            <th>Creato il</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($minigiochi as $minigioco)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $minigioco->title }}</div>
                                    <div class="small admin-muted">{{ \Illuminate\Support\Str::limit($minigioco->description, 90) ?: 'Nessuna descrizione' }}</div>
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
                                <td>{{ $minigioco->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="{{ route('admin.minigiochi.rounds.index', $minigioco->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-question-square"></i>
                                            Domande
                                        </a>
                                        <a href="{{ route('admin.minigiochi.edit', $minigioco->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                            Modifica
                                        </a>
                                        <a href="{{ route('admin.minigiochi.leaderboard', $minigioco->id) }}" class="btn btn-sm btn-dark">
                                            <i class="bi bi-trophy"></i>
                                            Classifica
                                        </a>
                                        <form action="{{ route('admin.minigiochi.destroy', $minigioco->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Sei sicuro di voler eliminare questo minigioco?')">
                                                <i class="bi bi-trash"></i>
                                                Elimina
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
