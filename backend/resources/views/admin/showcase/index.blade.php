@extends('layouts.admin')

@section('title', 'Galleria Chi siamo')
@section('kicker', 'Vetrina')
@section('page-title', 'Galleria Chi siamo')

@section('content')
    <div class="admin-dashboard-grid">
        <div>
            @include('admin.showcase.partials.gallery', [
                'type' => 'testimonial',
                'title' => 'Testimonianze',
                'description' => 'Screenshot di messaggi e feedback ricevuti dalla community.',
                'images' => $testimonials,
            ])

            @include('admin.showcase.partials.gallery', [
                'type' => 'collab',
                'title' => 'Collaborazioni',
                'description' => 'Screenshot di collaborazioni con pagine, creator e progetti.',
                'images' => $collabs,
            ])

            @include('admin.showcase.partials.gallery', [
                'type' => 'feedback',
                'title' => 'Feedback',
                'description' => 'Screenshot di feedback da mostrare nella home page del sito.',
                'images' => $feedbacks,
            ])
        </div>

        <section class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-section-title">Carica immagine</h2>
            </div>
            <div class="admin-card-body">
                <form action="{{ route('admin.showcase.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Sezione</label>
                        <select class="form-select" name="type" required>
                            <option value="testimonial">Testimonianze</option>
                            <option value="collab">Collaborazioni</option>
                            <option value="feedback">Feedback</option>
                        </select>
                        @error('type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Didascalia <span class="text-muted">(opzionale)</span></label>
                        <input class="form-control" name="caption" maxlength="120" value="{{ old('caption') }}">
                        @error('caption')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Immagine (jpg, png, webp - max 4MB)</label>
                        <input type="file" class="form-control" name="image" accept="image/png,image/jpeg,image/webp" required>
                        @error('image')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-upload"></i>
                        Carica
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
