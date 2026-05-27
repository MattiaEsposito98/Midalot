<section>
    <header class="mb-4">
        <h2 class="admin-section-title text-danger">Elimina account</h2>
        <p class="admin-muted mb-0">
            L'eliminazione dell'account e permanente e rimuove i dati collegati.
        </p>
    </header>

    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete-account">
        <i class="bi bi-trash"></i>
        Elimina account
    </button>

    <div class="modal fade" id="delete-account" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
        role="dialog" aria-labelledby="delete-account-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delete-account-title">Conferma eliminazione account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        Inserisci la password per confermare l'eliminazione permanente dell'account.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>

                    <form method="post" action="{{ route('profile.destroy') }}" class="flex-grow-1">
                        @csrf
                        @method('delete')

                        <div class="input-group">
                            <input id="delete_password" name="password" type="password"
                                class="form-control @if ($errors->userDeletion->has('password')) is-invalid @endif"
                                placeholder="Password">

                            <button type="submit" class="btn btn-danger">
                                Elimina account
                            </button>

                            @if ($errors->userDeletion->has('password'))
                                <div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
