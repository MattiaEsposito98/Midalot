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
                    <p class="admin-muted mb-0">Organizza i training per argomento.</p>
                </div>
                <a href="{{ route('admin.training.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">
                    Training quiz
                </a>
            </div>

            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Descrizione</th>
                            <th>Quiz</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>
                                    <div class="admin-title-cell">
                                        <span class="admin-thumb">
                                            @if ($category->image_path)
                                                <img src="{{ $category->image_url }}" alt="">
                                            @else
                                                <i class="bi bi-image admin-thumb-empty"></i>
                                            @endif
                                        </span>
                                        <div class="admin-title-cell-text fw-bold">{{ $category->name }}</div>
                                    </div>
                                </td>
                                <td>{{ $category->description ?: '-' }}</td>
                                <td>{{ $category->quizzes_count }}</td>
                                <td>
                                    <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $category->is_active ? 'Attiva' : 'Non attiva' }}
                                    </span>
                                </td>
                                <td>
                                    <form class="d-grid gap-2" action="{{ route('admin.training.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <input class="form-control form-control-sm" name="name" value="{{ $category->name }}" required>
                                        <textarea class="form-control form-control-sm" name="description" rows="2">{{ $category->description }}</textarea>
                                        <select class="form-select form-select-sm" name="is_active">
                                            <option value="1" {{ $category->is_active ? 'selected' : '' }}>Attiva</option>
                                            <option value="0" {{ !$category->is_active ? 'selected' : '' }}>Non attiva</option>
                                        </select>
                                        <input type="file" class="form-control form-control-sm" name="image" accept=".jpg,.jpeg,.png,image/*">
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
                                        <button class="btn btn-sm btn-danger w-100" onclick="return confirm('Eliminare questa categoria?')">
                                            Elimina
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"><div class="admin-empty">Nessuna categoria training.</div></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
