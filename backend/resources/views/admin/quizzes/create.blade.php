@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Crea Nuovo Quiz</h1>

        <form action="{{ route('admin.quizzes.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Titolo</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descrizione</label>
                <textarea name="description" class="form-control"></textarea>
            </div>

            <button type="submit" class="btn btn-success">
                Salva Quiz
            </button>
        </form>
    </div>
@endsection
