@extends('layouts.admin')

@section('title', 'Domande minigioco')
@section('kicker', 'Gestione domande')
@section('page-title', 'Domande')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $minigioco->title }}</h2>
                <p class="admin-muted mb-0">
                    @if ($minigioco->tipo === 'salto_temporale')
                        Gestisci i puzzle di ordinamento cronologico del minigioco "Salto Temporale".
                    @elseif ($minigioco->tipo === 'trova_intruso')
                        Gestisci i puzzle di "Trova l'Intruso".
                    @else
                        Gestisci le parole cifrate del minigioco "Tastiera Rotta".
                    @endif
                </p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.minigiochi.rounds.create', $minigioco->id) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i>
                    Aggiungi domanda
                </a>
                <a href="{{ route('admin.minigiochi.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Torna ai minigiochi
                </a>
            </div>
        </div>

        @if ($rounds->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-keyboard fs-1 d-block mb-2"></i>
                    <p class="mb-3">Nessuna domanda ancora inserita.</p>
                    <a href="{{ route('admin.minigiochi.rounds.create', $minigioco->id) }}" class="btn btn-primary">Aggiungi domanda</a>
                </div>
            </div>
        @elseif ($minigioco->tipo === 'tastiera_rotta')
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Parola</th>
                            <th>Parola cifrata</th>
                            <th>Shift</th>
                            <th>Timer</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rounds as $round)
                            <tr>
                                <td class="fw-bold">{{ $round->parola_originale }}</td>
                                <td><span class="badge bg-dark">{{ $round->parola_cifrata }}</span></td>
                                <td>
                                    {{ abs($round->shift) }}
                                    {{ $round->shift >= 0 ? 'a destra' : 'a sinistra' }}
                                </td>
                                <td>{{ $round->time_limit_seconds }} sec</td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="{{ route('admin.minigiochi.rounds.edit', [$minigioco->id, $round->id]) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                            Modifica
                                        </a>
                                        <form action="{{ route('admin.minigiochi.rounds.destroy', [$minigioco->id, $round->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Sei sicuro di voler eliminare questa domanda?')">
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
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Elementi</th>
                            @if ($minigioco->tipo === 'trova_intruso')
                                <th>Intruso</th>
                            @endif
                            <th>Timer</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rounds as $round)
                            @php $intrusoItem = $round->items->firstWhere('is_intruso', true); @endphp
                            <tr>
                                <td>
                                    @foreach ($round->items as $item)
                                        <span class="badge bg-secondary me-1 mb-1">{{ $item->label }}</span>
                                    @endforeach
                                </td>
                                @if ($minigioco->tipo === 'trova_intruso')
                                    <td><span class="badge bg-danger">{{ $intrusoItem?->label ?? '-' }}</span></td>
                                @endif
                                <td>{{ $round->time_limit_seconds }} sec</td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="{{ route('admin.minigiochi.rounds.edit', [$minigioco->id, $round->id]) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                            Modifica
                                        </a>
                                        <form action="{{ route('admin.minigiochi.rounds.destroy', [$minigioco->id, $round->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Sei sicuro di voler eliminare questo puzzle?')">
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
