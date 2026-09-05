@extends('layouts.admin')

@section('title', 'Utenti')
@section('kicker', 'Anagrafica')
@section('page-title', 'Utenti')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Utenti registrati</h2>
                <p class="admin-muted mb-0">Elenco degli utenti iscritti alla piattaforma.</p>
            </div>
        </div>

        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center mb-3">
                <div class="col-auto flex-grow-1">
                    <input type="search" name="search" value="{{ $search }}" class="form-control"
                        placeholder="Cerca per nome, nickname o email...">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                        Cerca
                    </button>
                </div>
                @if ($search)
                    <div class="col-auto">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            Azzera
                        </a>
                    </div>
                @endif
            </form>
        </div>

        @if ($users->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                    @if ($search)
                        <p class="mb-0">Nessun utente trovato per "{{ $search }}".</p>
                    @else
                        <p class="mb-0">Nessun utente registrato.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Nickname</th>
                            <th>Email</th>
                            <th>Telefono</th>
                            <th>Citta</th>
                            <th>Data di nascita</th>
                            <th>Registrato il</th>
                            <th>Email verificata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="fw-bold">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none">
                                        {{ $user->name }}
                                    </a>
                                </td>
                                <td><x-nickname-badge :user="$user" /></td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?: '-' }}</td>
                                <td>{{ $user->city?->name ?? '-' }}</td>
                                <td>{{ $user->birth_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if ($user->email_verified_at)
                                        <span class="badge bg-success">Si</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
