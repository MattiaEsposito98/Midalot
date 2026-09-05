@extends('layouts.admin')

@section('title', 'Crea training')
@section('kicker', 'Training')
@section('page-title', 'Crea training quiz')

@section('content')
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">Nuovo training quiz</h2>
                <p class="admin-muted mb-0">Dopo il salvataggio potrai aggiungere le domande.</p>
            </div>
            <a href="{{ route('admin.training.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">
                Torna ai training
            </a>
        </div>
        <div class="admin-card-body">
            @if ($categories->isEmpty())
                <div class="alert alert-warning">
                    Crea almeno una categoria attiva prima di aggiungere training quiz.
                    <a href="{{ route('admin.training.categories.index') }}">Gestisci categorie</a>
                </div>
            @endif

            <form action="{{ route('admin.training.quizzes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="admin-form-grid">
                    <div class="full">
                        <label class="form-label">Titolo</label>
                        <input class="form-control" name="title" value="{{ old('title') }}" required>
                    </div>
                    <div class="full">
                        <label class="form-label">Descrizione</label>
                        <textarea class="form-control" name="description" rows="4">{{ old('description') }}</textarea>
                    </div>
                    <div class="full">
                        <label class="form-label">Immagine di copertina (opzionale)</label>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,image/*">
                        <small class="admin-muted">JPG o PNG, max 2MB. Se non caricata, il training appare senza immagine come oggi.</small>
                    </div>
                    <div>
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="training_category_id" id="training_category_id" required>
                            <option value="">Seleziona...</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('training_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sottocategoria (opzionale)</label>
                        <select class="form-select" name="training_subcategory_id" id="training_subcategory_id">
                            <option value="">Nessuna</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Domande da estrarre</label>
                        <select class="form-select" name="training_question_mode" required>
                            <option value="5" {{ old('training_question_mode') === '5' ? 'selected' : '' }}>5</option>
                            <option value="10" {{ old('training_question_mode') === '10' ? 'selected' : '' }}>10</option>
                            <option value="all" {{ old('training_question_mode') === 'all' ? 'selected' : '' }}>Tutte</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Stato</label>
                        <select class="form-select" name="is_active">
                            <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Attivo</option>
                            <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Non attivo</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.training.quizzes.index') }}" class="btn btn-outline-secondary">Annulla</a>
                    <button class="btn btn-primary" @disabled($categories->isEmpty())>
                        Salva training
                    </button>
                </div>
            </form>
        </div>
    </section>

    <script>
        (function () {
            const subcategories = @json($subcategories->values());
            const oldSubcategoryId = @json(old('training_subcategory_id'));
            const categorySelect = document.getElementById('training_category_id');
            const subcategorySelect = document.getElementById('training_subcategory_id');

            function renderSubcategories() {
                const categoryId = categorySelect.value;
                subcategorySelect.innerHTML = '<option value="">Nessuna</option>';

                subcategories
                    .filter((sub) => String(sub.training_category_id) === String(categoryId))
                    .forEach((sub) => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name;
                        if (oldSubcategoryId && String(oldSubcategoryId) === String(sub.id)) {
                            option.selected = true;
                        }
                        subcategorySelect.appendChild(option);
                    });
            }

            categorySelect.addEventListener('change', renderSubcategories);
            renderSubcategories();
        })();
    </script>
@endsection
