@extends('layouts.admin')

@section('title', 'Categorie minigiochi')
@section('kicker', 'Gestione contenuti')
@section('page-title', 'Categorie minigiochi')

@section('content')
    <div class="admin-dashboard-grid">
        <section class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-section-title">Categorie</h2>
                    <p class="admin-muted mb-0">Organizza i minigiochi per argomento.</p>
                </div>
                <a href="{{ route('admin.minigiochi.index') }}" class="btn btn-outline-secondary btn-sm">
                    Minigiochi
                </a>
            </div>

            <div class="admin-card-body">
                @if ($categories->isEmpty())
                    <div class="admin-empty">Nessuna categoria minigiochi.</div>
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
                                                <div class="small admin-muted">{{ \Illuminate\Support\Str::limit($category->description, 90) ?: 'Nessuna descrizione' }}</div>
                                            </div>
                                            <span class="badge bg-primary-subtle text-dark border">
                                                {{ $category->minigiochi_count }} minigiochi
                                            </span>
                                            <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $category->is_active ? 'Attiva' : 'Non attiva' }}
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-category-{{ $category->id }}" class="accordion-collapse collapse" data-bs-parent="#categoriesAccordion">
                                    <div class="accordion-body">
                                        <form class="d-grid gap-2" action="{{ route('admin.minigiochi.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
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
                                        <form action="{{ route('admin.minigiochi.categories.destroy', $category) }}" method="POST" class="mt-2">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare questa categoria?')">
                                                Elimina categoria
                                            </button>
                                        </form>
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
                <form action="{{ route('admin.minigiochi.categories.store') }}" method="POST" enctype="multipart/form-data">
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
