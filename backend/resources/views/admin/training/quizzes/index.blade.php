@extends('layouts.admin')

@section('title', 'Training quiz')
@section('kicker', 'Training')
@section('page-title', 'Training quiz')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Quiz di allenamento</h2>
                <p class="admin-muted mb-0">Gestisci i quiz pubblici disponibili per ospiti e utenti registrati.</p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.training.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                    Categorie
                </a>
                <a href="{{ route('admin.training.quizzes.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i>
                    Crea training
                </a>
            </div>
        </div>

        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.training.quizzes.index') }}" class="row g-2 align-items-center mb-3">
                <div class="col-auto flex-grow-1" style="min-width: 200px;">
                    <input type="search" name="search" value="{{ $search }}" class="form-control"
                        placeholder="Cerca per titolo o categoria...">
                </div>
                <div class="col-auto">
                    <select class="form-select" name="category" id="filter_category">
                        <option value="">Tutte le categorie</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="subcategory" id="filter_subcategory">
                        <option value="">Tutte le sottocategorie</option>
                        @foreach ($subcategories as $subcategory)
                            <option
                                value="{{ $subcategory->id }}"
                                data-category="{{ $subcategory->training_category_id }}"
                                class="{{ (string) $categoryId !== '' && (string) $categoryId !== (string) $subcategory->training_category_id ? 'd-none' : '' }}"
                                {{ (string) $subcategoryId === (string) $subcategory->id ? 'selected' : '' }}
                            >
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                        Filtra
                    </button>
                </div>
                @if ($search || $categoryId || $subcategoryId)
                    <div class="col-auto">
                        <a href="{{ route('admin.training.quizzes.index') }}" class="btn btn-outline-secondary">
                            Azzera
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <script>
            (function () {
                const categorySelect = document.getElementById('filter_category');
                const subcategorySelect = document.getElementById('filter_subcategory');

                categorySelect.addEventListener('change', function () {
                    const categoryId = categorySelect.value;

                    Array.from(subcategorySelect.options).forEach((option) => {
                        if (!option.value) return;
                        const belongs = !categoryId || option.dataset.category === categoryId;
                        option.classList.toggle('d-none', !belongs);
                        if (!belongs && option.selected) {
                            subcategorySelect.value = '';
                        }
                    });
                });
            })();
        </script>

        @if ($quizzes->isEmpty())
            <div class="admin-empty">
                <div>
                    <i class="bi bi-lightning-charge fs-1 d-block mb-2"></i>
                    @if ($search)
                        <p class="mb-3">Nessun training trovato per "{{ $search }}".</p>
                    @else
                        <p class="mb-3">Nessun training quiz creato.</p>
                        <a href="{{ route('admin.training.quizzes.create') }}" class="btn btn-primary">Crea training</a>
                    @endif
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Training</th>
                            <th>Categoria</th>
                            <th>Sottocategoria</th>
                            <th>Domande estratte</th>
                            <th>Domande create</th>
                            <th>Stato</th>
                            <th>Validita</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quizzes as $quiz)
                            @php
                                $required = $quiz->training_question_mode === 'all' ? 1 : (int) $quiz->training_question_mode;
                                $valid = $quiz->questions_count >= $required;
                            @endphp
                            <tr>
                                <td>
                                    <div class="admin-title-cell">
                                        <span class="admin-thumb">
                                            @if ($quiz->image_path)
                                                <img src="{{ $quiz->image_url }}" alt="">
                                            @else
                                                <i class="bi bi-image admin-thumb-empty"></i>
                                            @endif
                                        </span>
                                        <div class="admin-title-cell-text">
                                            <div class="fw-bold">{{ $quiz->title }}</div>
                                            <div class="small admin-muted">{{ \Illuminate\Support\Str::limit($quiz->description, 80) ?: 'Nessuna descrizione' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $quiz->trainingCategory?->name ?? '-' }}</td>
                                <td>{{ $quiz->trainingSubcategory?->name ?? '-' }}</td>
                                <td>{{ $quiz->training_question_mode === 'all' ? 'Tutte' : $quiz->training_question_mode }}</td>
                                <td>{{ $quiz->questions_count }}</td>
                                <td>
                                    <span class="badge {{ $quiz->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $quiz->is_active ? 'Attivo' : 'Non attivo' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $valid ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ $valid ? 'Giocabile' : 'Aggiungi domande' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-sm btn-outline-primary">
                                            Domande
                                        </a>
                                        <a href="{{ route('admin.training.quizzes.edit', $quiz) }}" class="btn btn-sm btn-warning">
                                            Modifica
                                        </a>
                                        <a href="{{ route('admin.training.quizzes.leaderboard', $quiz) }}" class="btn btn-sm btn-dark">
                                            <i class="bi bi-trophy"></i>
                                            Classifica
                                        </a>
                                        <form action="{{ route('admin.training.quizzes.destroy', $quiz) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Eliminare questo training quiz?')">
                                                Elimina
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($quizzes->hasPages())
                <div class="mt-3">
                    {{ $quizzes->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
