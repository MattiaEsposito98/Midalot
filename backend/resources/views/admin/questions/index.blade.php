@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Domande Quiz: {{ $quiz->title }}</h1>
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <div class="d-flex justify-content-between align-center mt-3">
            <a href="{{ route('admin.quizzes.questions.create', $quiz->id) }}" class="btn btn-primary ">
                + Aggiungi Domanda
            </a>

            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-secondary ">
                ← Torna ai Quiz
            </a>
        </div>


        @if ($questions->isEmpty())
            <p>Nessuna domanda ancora inserita.</p>
        @else
            <table class="table">
                <thead>
                    <thead>
                        <tr>
                            <th>Ordine</th>
                            <th>Testo</th>
                            <th>Immagine</th>
                            <th>Audio</th>
                            <th>Video</th>
                            <th>Timer (sec)</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                </thead>
                <tbody>
                    @foreach ($questions as $question)
                        <tr>
                            <td>{{ $question->order }}</td>

                            <td>
                                {{ \Illuminate\Support\Str::limit($question->question_text, 50) }}
                            </td>

                            <td>
                                @if ($question->image_path)
                                    <a href="{{ asset('storage/' . $question->image_path) }}" target="_blank">

                                        <img src="{{ asset('storage/' . $question->image_path) }}" width="80"
                                            class="border rounded" style="cursor:pointer;">
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($question->audio_path)
                                    <a href="{{ asset('storage/' . $question->audio_path) }}" target="_blank">
                                        🎧 Audio
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                @if ($question->video_path)
                                    <a href="{{ asset('storage/' . $question->video_path) }}" target="_blank">
                                        🎬 Video
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

                            <td>{{ $question->time_limit_seconds }}</td>
                            <td>
                                <a href="{{ route('admin.quizzes.questions.edit', [$quiz->id, $question->id]) }}"
                                    class="btn btn-sm btn-warning">
                                    Modifica
                                </a>

                                <form action="{{ route('admin.quizzes.questions.destroy', [$quiz->id, $question->id]) }}"
                                    method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Sei sicuro di voler eliminare questa domanda?')">
                                        Elimina
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>
@endsection
