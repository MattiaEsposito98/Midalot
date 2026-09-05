@extends('layouts.admin')

@section('title', 'Categorie training')
@section('kicker', 'Training')
@section('page-title', 'Categorie training')

@section('content')
    <div class="admin-dashboard-grid">
        <section class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-section-title">Categorie</h2>
                    <p class="admin-muted mb-0">Organizza i training per argomento. Apri una categoria per gestirla e vedere le sue sottocategorie.</p>
                </div>
                <a href="{{ route('admin.training.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">
                    Training quiz
                </a>
            </div>

            <div class="admin-card-body">
                @if ($categories->isEmpty())
                    <div class="admin-empty">Nessuna categoria training.</div>
                @else
                    <div class="accordion" id="categoriesAccordion">
                        @foreach ($categories as $category)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-category-{{ $category->id }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-category-{{ $category->id }}">
                                        <div class="d-flex align-items-center gap-3 w-100 flex-wrap">
                                            <span class="admin-thumb">
                                                @if ($category->image_path)
                                                    <img src="{{ $category->image_url }}" alt="">
                                                @else
                                                    <i class="bi bi-image admin-thumb-empty"></i>
                                                @endif
                                            </span>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">{{ $category->name }}</div>
                                                <div class="small admin-muted">{{ Illuminate\Support\Str::limit($category->description, 90) ?: 'Nessuna descrizione' }}</div>
                                            </div>
                                            <span class="badge bg-info-subtle text-dark border">
                                                {{ $category->subcategories->count() }} sottocategorie
                                            </span>
                                            <span class="badge bg-primary-subtle text-dark border">
                                                {{ $category->quizzes_count }} quiz
                                            </span>
                                            <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $category->is_active ? 'Attiva' : 'Non attiva' }}
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-category-{{ $category->id }}" class="accordion-collapse collapse" data-bs-parent="#categoriesAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-4">
                                            <div class="col-lg-5">
                                                <h3 class="admin-section-subtitle">Modifica categoria</h3>
                                                <form class="d-grid gap-2" action="{{ route('admin.training.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <div>
                                                        <label class="form-label small">Nome</label>
                                                        <input class="form-control form-control-sm" name="name" value="{{ $category->name }}" required>
                                                    </div>
                                                    <div>
                                                        <label class="form-label small">Descrizione</label>
                                                        <textarea class="form-control form-control-sm" name="description" rows="2">{{ $category->description }}</textarea>
                                                    </div>
                                                    <div>
                                                        <label class="form-label small">Stato</label>
                                                        <select class="form-select form-select-sm" name="is_active">
                                                            <option value="1" {{ $category->is_active ? 'selected' : '' }}>Attiva</option>
                                                            <option value="0" {{ !$category->is_active ? 'selected' : '' }}>Non attiva</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="form-label small">Immagine di copertina</label>
                                                        <input type="file" class="form-control form-control-sm" name="image" accept=".jpg,.jpeg,.png,image/*">
                                                    </div>
                                                    @if ($category->image_path)
                                                        <div class="form-check">
                                                            <input type="checkbox" name="remove_image" value="1" class="form-check-input" id="remove_image_{{ $category->id }}">
                                                            <label class="form-check-label small text-danger" for="remove_image_{{ $category->id }}">Elimina immagine</label>
                                                        </div>
                                                    @endif
                                                    <button class="btn btn-sm btn-primary">Salva</button>
                                                </form>
                                                <form action="{{ route('admin.training.categories.destroy', $category) }}" method="POST" class="mt-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare questa categoria?')">
                                                        Elimina categoria
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="col-lg-7">
                                                <h3 class="admin-section-subtitle">Sottocategorie</h3>
                                                <ul class="list-unstyled d-grid gap-2 mb-3">
                                                    @forelse ($category->subcategories as $subcategory)
                                                        <li class="admin-subcategory-row">
                                                            <form class="d-flex align-items-center gap-1 flex-grow-1 flex-wrap" action="{{ route('admin.training.subcategories.update', $subcategory) }}" method="POST" enctype="multipart/form-data">
                                                                @csrf
                                                                @method('PUT')
                                                                <span class="admin-thumb" style="width: 36px; height: 36px;">
                                                                    @if ($subcategory->image_path)
                                                                        <img src="{{ $subcategory->image_url }}" alt="">
                                                                    @else
                                                                        <i class="bi bi-image admin-thumb-empty" style="font-size: .9rem;"></i>
                                                                    @endif
                                                                </span>
                                                                <input class="form-control form-control-sm" style="max-width: 130px;" name="name" value="{{ $subcategory->name }}" required>
                                                                <select class="form-select form-select-sm" name="is_active" style="max-width: 90px;">
                                                                    <option value="1" {{ $subcategory->is_active ? 'selected' : '' }}>Attiva</option>
                                                                    <option value="0" {{ !$subcategory->is_active ? 'selected' : '' }}>Off</option>
                                                                </select>
                                                                <input type="file" class="form-control form-control-sm" style="max-width: 150px;" name="image" accept=".jpg,.jpeg,.png,image/*">
                                                                @if ($subcategory->image_path)
                                                                    <div class="form-check form-check-inline m-0">
                                                                        <input type="checkbox" name="remove_image" value="1" class="form-check-input" id="remove_sub_image_{{ $subcategory->id }}">
                                                                        <label class="form-check-label small text-danger" for="remove_sub_image_{{ $subcategory->id }}">Elimina img</label>
                                                                    </div>
                                                                @endif
                                                                <span class="small admin-muted text-nowrap">{{ $subcategory->quizzes_count }} quiz</span>
                                                                <button class="btn btn-sm btn-outline-primary" title="Salva"><i class="bi bi-check-lg"></i></button>
                                                            </form>
                                                            <form action="{{ route('admin.training.subcategories.destroy', $subcategory) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn btn-sm btn-link text-danger" title="Elimina" onclick="return confirm('Eliminare questa sottocategoria?')">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @empty
                                                        <li class="admin-muted small">Nessuna sottocategoria.</li>
                                                    @endforelse
                                                </ul>
                                                <form action="{{ route('admin.training.subcategories.store', $category) }}" method="POST" enctype="multipart/form-data" class="d-flex gap-1 flex-wrap">
                                                    @csrf
                                                    <input class="form-control form-control-sm" style="max-width: 160px;" name="name" placeholder="Nuova sottocategoria" required>
                                                    <input type="file" class="form-control form-control-sm" style="max-width: 160px;" name="image" accept=".jpg,.jpeg,.png,image/*">
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button class="btn btn-sm btn-outline-secondary text-nowrap" title="Aggiungi">
                                                        <i class="bi bi-plus-lg"></i>
                                                        Aggiungi
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-section-title">Nuova categoria</h2>
            </div>
            <div class="admin-card-body">
                <form action="{{ route('admin.training.categories.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrizione</label>
                        <textarea class="form-control" name="description" rows="4">{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Immagine di copertina (opzionale)</label>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,image/*">
                        <small class="admin-muted">JPG o PNG, max 2MB.</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Stato</label>
                        <select class="form-select" name="is_active">
                            <option value="1">Attiva</option>
                            <option value="0">Non attiva</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Crea categoria
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
