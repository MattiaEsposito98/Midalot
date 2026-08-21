<section class="admin-card mb-3">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-section-title">{{ $title }}</h2>
            <p class="admin-muted mb-0">{{ $description }}</p>
        </div>
        <span class="badge bg-secondary">{{ $images->count() }}</span>
    </div>

    <div class="admin-card-body">
        @if ($images->isEmpty())
            <div class="admin-empty">Nessuna immagine in questa sezione.</div>
        @else
            <div class="row g-3">
                @foreach ($images as $image)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="border rounded overflow-hidden h-100 d-flex flex-column">
                            <img src="{{ $image->image_url }}" alt="{{ $image->caption }}" class="w-100" style="aspect-ratio: 1 / 1; object-fit: cover;">
                            <div class="p-2 d-flex flex-column gap-1">
                                <small class="admin-muted text-truncate">{{ $image->caption ?: 'Senza didascalia' }}</small>
                                <small class="admin-muted">{{ $image->created_at->format('d/m/Y') }}</small>
                                <form action="{{ route('admin.showcase.destroy', $image) }}" method="POST" onsubmit="return confirm('Eliminare questa immagine?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger w-100">
                                        <i class="bi bi-trash"></i>
                                        Elimina
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
