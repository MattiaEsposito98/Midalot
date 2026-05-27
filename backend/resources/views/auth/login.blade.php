@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold">
                        Accesso amministratori
                    </div>

                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Questa area è riservata agli amministratori Midalot.
                        </p>

                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="login" class="form-label">Email o nickname admin</label>
                                <input id="login" type="text"
                                    class="form-control @error('login') is-invalid @enderror" name="login"
                                    value="{{ old('login') }}" required autofocus>

                                @error('login')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password" required
                                    autocomplete="current-password">

                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                        {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        Ricordami
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    Entra nell'admin
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        Password dimenticata?
                                    </a>
                                @endif
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <span class="text-muted">Sei un utente?</span>
                            <a class="btn btn-outline-secondary" href="{{ rtrim(config('app.frontend_url'), '/') }}/login">
                                Accedi dal sito utenti
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
