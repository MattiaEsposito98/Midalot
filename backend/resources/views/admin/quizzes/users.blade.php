@extends('layouts.admin')

@section('title', 'Associa utenti')
@section('kicker', 'Gestione utenti')
@section('page-title', 'Associa utenti')

@section('content')
    <div class="admin-dashboard-grid">
        <section class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-section-title">{{ $quiz->title }}</h2>
                    <p class="admin-muted mb-0">Cerca utenti e associali al quiz selezionato.</p>
                </div>
                <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Torna ai quiz
                </a>
            </div>

            <div class="admin-card-body">
                <div class="mb-3">
                    <label class="form-label">Cerca utente</label>
                    <input type="text" id="userSearch" class="form-control" placeholder="Scrivi nickname o email..." autocomplete="off">
                </div>

                <div id="searchResults" class="list-group mb-4"></div>

                <h3 class="admin-section-title mb-3">Utenti da associare</h3>
                <ul id="selectedUsers" class="list-group mb-3"></ul>

                <form method="POST" action="{{ route('admin.quizzes.attachUsers', $quiz->id) }}">
                    @csrf
                    <div id="hiddenInputs"></div>
                    <button class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Conferma associazione
                    </button>
                </form>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-section-title">Utenti gia associati</h2>
            </div>
            <div class="list-group list-group-flush">
                @forelse($attachedUsers as $user)
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-bold">{{ $user->nickname }}</div>
                            <div class="small admin-muted">{{ $user->email }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.quizzes.detachUser', [$quiz->id, $user->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">
                                <i class="bi bi-x-lg"></i>
                                Rimuovi
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="admin-empty">Nessun utente associato.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        let selected = {};
        let lastResults = [];

        const searchInput = document.getElementById('userSearch');
        const resultsContainer = document.getElementById('searchResults');

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            if (query.length < 2) {
                resultsContainer.innerHTML = '';
                return;
            }

            fetch(`{{ route('admin.quizzes.users.search', $quiz->id) }}?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    lastResults = data;
                    renderResults();
                });
        });

        function renderResults() {
            let html = '';

            lastResults.forEach(user => {
                if (selected[user.id]) return;

                html += `
                    <button type="button"
                        class="list-group-item list-group-item-action"
                        onclick="addUserById(${user.id})">
                        <strong>${escapeHtml(user.nickname ?? 'Utente')}</strong>
                        <span class="admin-muted">${escapeHtml(user.email ?? '')}</span>
                    </button>
                `;
            });

            resultsContainer.innerHTML = html || '<div class="list-group-item admin-muted">Nessun utente trovato.</div>';
        }

        function addUserById(id) {
            const user = lastResults.find(item => Number(item.id) === Number(id));

            if (!user) return;

            if (selected[user.id]) return;

            selected[user.id] = {
                nickname: user.nickname,
                email: user.email
            };

            renderSelected();
            renderResults();
        }

        function renderSelected() {
            let list = '';
            let hidden = '';

            Object.keys(selected).forEach(id => {
                const user = selected[id];

                list += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <strong>${escapeHtml(user.nickname ?? 'Utente')}</strong>
                            <span class="admin-muted">${escapeHtml(user.email ?? '')}</span>
                        </span>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeUser(${id})">
                            Rimuovi
                        </button>
                    </li>
                `;

                hidden += `<input type="hidden" name="users[]" value="${id}">`;
            });

            document.getElementById('selectedUsers').innerHTML = list || '<li class="list-group-item admin-muted">Nessun utente selezionato.</li>';
            document.getElementById('hiddenInputs').innerHTML = hidden;
        }

        function removeUser(id) {
            delete selected[id];
            renderSelected();
            renderResults();
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        renderSelected();
    </script>
@endpush
