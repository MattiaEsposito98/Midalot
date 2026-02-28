@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Associa utenti al quiz: {{ $quiz->title }}</h2>

            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary">
                ← Torna ai Quiz
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- SEARCH -->
        <div class="mb-3">
            <label>Cerca utente</label>
            <input type="text" id="userSearch" class="form-control" placeholder="Scrivi nickname o email..."
                autocomplete="off">
        </div>

        <div id="searchResults" class="list-group mb-4"></div>

        <!-- SELEZIONATI -->
        <h5>Utenti da associare:</h5>
        <ul id="selectedUsers" class="list-group mb-3"></ul>

        <form method="POST" action="{{ route('admin.quizzes.attachUsers', $quiz->id) }}">
            @csrf
            <div id="hiddenInputs"></div>

            <button class="btn btn-success">
                Conferma Associazione
            </button>
        </form>

        <hr>

        <!-- GIÀ ASSOCIATI -->
        <h5>Utenti già associati:</h5>
        <ul class="list-group">
            @forelse($attachedUsers as $user)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    {{ $user->nickname }} ({{ $user->email }})

                    <form method="POST" action="{{ route('admin.quizzes.detachUser', [$quiz->id, $user->id]) }}">
                        @csrf
                        @method('DELETE')

                        <button class="btn btn-sm btn-danger">
                            Rimuovi
                        </button>
                    </form>
                </li>
            @empty
                <li class="list-group-item text-muted">
                    Nessun utente associato.
                </li>
            @endforelse
        </ul>

    </div>

    <script>
        let selected = {};
        let lastResults = [];

        const searchInput = document.getElementById('userSearch');
        const resultsContainer = document.getElementById('searchResults');

        searchInput.addEventListener('input', function() {

            let query = this.value.trim();

            if (query.length < 2) {
                resultsContainer.innerHTML = '';
                return;
            }

            fetch(`{{ route('admin.quizzes.users.search', $quiz->id) }}?q=${query}`)
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
                onclick="addUser(${user.id}, '${user.nickname}', '${user.email}')">
                ${user.nickname} (${user.email})
            </button>
        `;
            });

            resultsContainer.innerHTML = html;
        }

        function addUser(id, nickname, email) {

            if (selected[id]) return;

            selected[id] = {
                nickname,
                email
            };

            renderSelected();
            renderResults();
        }

        function renderSelected() {

            let list = '';
            let hidden = '';

            Object.keys(selected).forEach(id => {

                let user = selected[id];

                list += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                ${user.nickname} (${user.email})
                <button type="button"
                        class="btn btn-sm btn-danger"
                        onclick="removeUser(${id})">
                    X
                </button>
            </li>
        `;

                hidden += `<input type="hidden" name="users[]" value="${id}">`;
            });

            document.getElementById('selectedUsers').innerHTML = list;
            document.getElementById('hiddenInputs').innerHTML = hidden;
        }

        function removeUser(id) {
            delete selected[id];
            renderSelected();
            renderResults();
        }
    </script>
@endsection
