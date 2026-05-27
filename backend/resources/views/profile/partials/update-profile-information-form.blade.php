<section>
    <header class="mb-4">
        <h2 class="admin-section-title">Informazioni profilo</h2>
        <p class="admin-muted mb-0">Aggiorna nome ed email dell'account amministratore.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="admin-form-grid">
            <div>
                <label for="name" class="form-label">Nome</label>
                <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" id="name"
                    autocomplete="name" value="{{ old('name', $user->name) }}" required autofocus>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="email" class="form-label">Email</label>
                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email', $user->email) }}" required autocomplete="username">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="alert alert-warning mt-3 mb-0">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span>Il tuo indirizzo email non e verificato.</span>
                    <button form="send-verification" class="btn btn-outline-dark btn-sm">
                        Invia di nuovo la verifica
                    </button>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <p class="text-success mb-0 mt-2">Nuovo link di verifica inviato.</p>
                @endif
            </div>
        @endif

        <div class="d-flex align-items-center gap-3 mt-4">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg"></i>
                Salva profilo
            </button>

            @if (session('status') === 'profile-updated')
                <span class="text-success fw-semibold">Salvato.</span>
            @endif
        </div>
    </form>
</section>
