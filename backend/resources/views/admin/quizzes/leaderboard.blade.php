@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-0">Classifica Quiz: {{ $quiz->title }}</h1>



        <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-secondary">
                ← Torna ai quiz
            </a>

            <form action="{{ route('admin.quizzes.toggleLeaderboard', $quiz) }}" method="POST">
                @csrf
                @method('PATCH')

                <button type="submit" class="btn {{ $quiz->leaderboard_visible ? 'btn-success' : 'btn-outline-secondary' }}">
                    {{ $quiz->leaderboard_visible ? 'Classifica visibile agli utenti' : 'Rendi classifica visibile agli utenti' }}
                </button>
            </form>
        </div>


        <div class="mb-3">
            <strong>Stato classifica:</strong>
            @if ($quiz->leaderboard_visible)
                <span class="badge bg-success">Visibile</span>
            @else
                <span class="badge bg-secondary">Nascosta</span>
            @endif
        </div>

        @if ($results->isEmpty())
            <div class="alert alert-info">
                Nessun risultato disponibile per questo quiz.
            </div>
        @else
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Utente</th>
                        <th>Email</th>
                        <th>Punteggio</th>
                        <th>Risposte corrette</th>
                        <th>Tempo totale</th>
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $index => $r)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>{{ $r->user->nickname ?? ($r->user->name ?? '-') }}</td>
                            <td>{{ $r->user->email ?? '-' }}</td>

                            <td>
                                <strong>{{ $r->score ?? 0 }}</strong>
                            </td>

                            <td>
                                {{ $r->correct_answers ?? 0 }} / {{ $r->total_questions ?? 0 }}
                            </td>

                            <td>
                                @if (!is_null($r->total_time))
                                    @php
                                        $ms = (int) $r->total_time;
                                        $seconds = floor($ms / 1000);
                                        $minutes = floor($seconds / 60);
                                        $remainingSeconds = $seconds % 60;
                                        $milliseconds = $ms % 1000;
                                    @endphp

                                    {{ sprintf('%d:%02d.%03d', $minutes, $remainingSeconds, $milliseconds) }}
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                @if ($r->completed)
                                    <span class="badge bg-success">Completato</span>
                                @else
                                    <span class="badge bg-warning text-dark">In corso</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
