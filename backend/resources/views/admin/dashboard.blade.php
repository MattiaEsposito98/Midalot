@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Dashboard Admin</h1>

        <div class="mt-4">
            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-primary">
                Gestisci Quiz
            </a>
        </div>
    </div>
@endsection
