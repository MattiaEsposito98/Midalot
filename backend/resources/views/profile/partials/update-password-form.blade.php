<section>
    <header class="mb-4">
        <h2 class="admin-section-title">Password</h2>
        <p class="admin-muted mb-0">Mantieni l'account protetto con una password sicura.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="admin-form-grid">
            <div class="full">
                <label for="current_password" class="form-label">Password attuale</label>
                <input class="form-control @if ($errors->updatePassword->has('current_password')) is-invalid @endif"
                    type="password" name="current_password" id="current_password" autocomplete="current-password">
                @if ($errors->updatePassword->has('current_password'))
                    <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                @endif
            </div>

            <div>
                <label for="password" class="form-label">Nuova password</label>
                <input class="form-control @if ($errors->updatePassword->has('password')) is-invalid @endif"
                    type="password" name="password" id="password" autocomplete="new-password">
                @if ($errors->updatePassword->has('password'))
                    <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                @endif
            </div>

            <div>
                <label for="password_confirmation" class="form-label">Conferma password</label>
                <input class="form-control @if ($errors->updatePassword->has('password_confirmation')) is-invalid @endif"
                    type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password">
                @if ($errors->updatePassword->has('password_confirmation'))
                    <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-shield-check"></i>
                Aggiorna password
            </button>

            @if (session('status') === 'password-updated')
                <span class="text-success fw-semibold">Password aggiornata.</span>
            @endif
        </div>
    </form>
</section>
