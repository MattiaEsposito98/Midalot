@extends('layouts.admin')

@section('title', 'Pesca dai Training')
@section('kicker', 'Gestione domande')
@section('page-title', 'Pesca domande dai Training')
@section('activeNav', $quiz->type === 'midalario' ? 'midalario' : 'quizzes')

@php
    $f = $filters;
    $hasFilters = $f['search'] !== '' || $f['categoryId'] || $f['subcategoryId'] || $f['trainingId'] || $f['origin'];
@endphp

@section('content')
    <section class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $quiz->title }}</h2>
                <p class="admin-muted mb-0">
                    Scegli domande già pronte nei Training: verranno <strong>copiate</strong> in questo quiz con le loro 4 risposte.
                    Il quiz ha ora {{ $quizQuestionsCount }} {{ $quizQuestionsCount === 1 ? 'domanda' : 'domande' }}.
                    Sono mostrate solo le domande con 4 risposte.
                </p>
            </div>
            <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Torna alle domande
            </a>
        </div>

        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.quizzes.questions.picker', $quiz->id) }}" class="row g-2 align-items-center" id="picker_filters">
                <div class="col-12 col-lg">
                    <input type="search" name="search" value="{{ $f['search'] }}" class="form-control"
                        placeholder="Cerca nel testo della domanda o nelle risposte...">
                </div>
                <div class="col-auto">
                    <select class="form-select" name="category" id="pf_category">
                        <option value="">Tutte le categorie</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) $f['categoryId'] === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="subcategory" id="pf_subcategory">
                        <option value="">Tutte le sottocategorie</option>
                        @foreach ($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" data-category="{{ $subcategory->training_category_id }}"
                                {{ (string) $f['subcategoryId'] === (string) $subcategory->id ? 'selected' : '' }}>{{ $subcategory->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="training" id="pf_training">
                        <option value="">Tutti i training</option>
                        @foreach ($trainings as $training)
                            <option value="{{ $training->id }}" data-category="{{ $training->training_category_id }}"
                                data-subcategory="{{ $training->training_subcategory_id }}"
                                {{ (string) $f['trainingId'] === (string) $training->id ? 'selected' : '' }}>{{ $training->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="origin">
                        <option value="">Tutte le origini</option>
                        <option value="admin" {{ $f['origin'] === 'admin' ? 'selected' : '' }}>Scritte dall'admin</option>
                        <option value="ai" {{ $f['origin'] === 'ai' ? 'selected' : '' }}>Generate dall'AI</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                        Filtra
                    </button>
                </div>
                @if ($hasFilters)
                    <div class="col-auto">
                        <a href="{{ route('admin.quizzes.questions.picker', $quiz->id) }}" class="btn btn-outline-secondary">Azzera</a>
                    </div>
                @endif
            </form>
        </div>
    </section>

    @if ($questions->isEmpty())
        <section class="admin-card">
            <div class="admin-empty">
                <div>
                    <i class="bi bi-search fs-1 d-block mb-2"></i>
                    <p class="mb-0">Nessuna domanda trovata con questi filtri.</p>
                </div>
            </div>
        </section>
    @else
        <form method="POST" action="{{ route('admin.quizzes.questions.picker.store', $quiz->id) }}" id="picker_form">
            @csrf
            <section class="admin-card mb-3">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-section-title">{{ $questions->total() }} {{ $questions->total() === 1 ? 'domanda trovata' : 'domande trovate' }}</h2>
                        <p class="admin-muted mb-0">La selezione vale per questa pagina: aggiungi e poi passa alla successiva.</p>
                    </div>
                    <label class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" id="pick_all">
                        <span class="form-check-label">Seleziona tutte (di questa pagina)</span>
                    </label>
                </div>

                <div class="table-responsive">
                    <table class="table admin-table align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Domanda e risposte</th>
                                <th>Training</th>
                                <th>Timer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($questions as $question)
                                @php $already = isset($existing[$normalize($question->question_text)]); @endphp
                                <tr @class(['table-secondary' => $already])>
                                    <td>
                                        <input type="checkbox" class="form-check-input pick-item" name="question_ids[]"
                                            value="{{ $question->id }}" {{ $already ? 'disabled' : '' }}
                                            aria-label="Seleziona la domanda">
                                    </td>
                                    <td>
                                        @if ($already)
                                            <span class="badge text-bg-secondary">Già nel quiz</span>
                                        @endif
                                        @if ($question->isAi())
                                            <span class="badge text-bg-info">AI</span>
                                        @endif
                                        {{ $question->question_text }}
                                        <ul class="list-unstyled small mb-0 mt-1">
                                            @foreach ($question->answers->sortBy('id') as $answer)
                                                <li>
                                                    <i class="bi {{ $answer->is_correct ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i>
                                                    {{ $answer->answer_text }}
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($question->image_path || $question->audio_path || $question->itunes_preview_url || $question->video_path)
                                            <div class="small admin-muted mt-1">
                                                Con media:
                                                @if ($question->image_path)<i class="bi bi-image"></i> immagine @endif
                                                @if ($question->audio_path || $question->itunes_preview_url)<i class="bi bi-volume-up"></i> audio @endif
                                                @if ($question->video_path)<i class="bi bi-camera-video"></i> video @endif
                                                (copiati insieme alla domanda)
                                            </div>
                                        @endif
                                    </td>
                                    <td class="small">
                                        <div class="fw-semibold">{{ $question->quiz->title }}</div>
                                        <div class="admin-muted">
                                            {{ $question->quiz->trainingCategory?->name }}{{ $question->quiz->trainingSubcategory ? ' · ' . $question->quiz->trainingSubcategory->name : '' }}
                                        </div>
                                    </td>
                                    <td>{{ $question->time_limit_seconds }} sec</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="admin-card-body">{{ $questions->links() }}</div>
            </section>

            <section class="admin-card position-sticky" style="bottom: 0; z-index: 5;">
                <div class="admin-card-body d-flex flex-wrap align-items-center gap-3">
                    <strong><span id="pick_count">0</span> selezionate</strong>
                    <div class="d-flex align-items-center gap-2">
                        <label for="time_limit_seconds" class="form-label mb-0 small">Timer per tutte (sec, facoltativo)</label>
                        <input type="number" min="5" max="600" name="time_limit_seconds" id="time_limit_seconds"
                            class="form-control form-control-sm" style="width: 90px;" placeholder="originale">
                    </div>
                    <button type="submit" class="btn btn-success ms-auto" id="pick_submit" disabled>
                        <i class="bi bi-plus-lg"></i>
                        Aggiungi al quiz
                    </button>
                </div>
            </section>
        </form>
    @endif

    <script>
        (function () {
            // Filtri a cascata: categoria -> sottocategoria -> training.
            const cat = document.getElementById('pf_category');
            const sub = document.getElementById('pf_subcategory');
            const tr = document.getElementById('pf_training');

            function refresh() {
                const c = cat.value, s = sub.value;
                Array.from(sub.options).forEach((o) => {
                    if (!o.value) return;
                    const ok = !c || o.dataset.category === c;
                    o.hidden = !ok;
                    if (!ok && o.selected) sub.value = '';
                });
                Array.from(tr.options).forEach((o) => {
                    if (!o.value) return;
                    const ok = (!c || o.dataset.category === c) && (!sub.value || o.dataset.subcategory === sub.value);
                    o.hidden = !ok;
                    if (!ok && o.selected) tr.value = '';
                });
            }
            cat.addEventListener('change', refresh);
            sub.addEventListener('change', refresh);
            refresh();

            // I menu a tendina applicano subito il filtro.
            [cat, sub, tr].forEach((el) => el.addEventListener('change', () => document.getElementById('picker_filters').submit()));

            // Selezione multipla.
            const items = Array.from(document.querySelectorAll('.pick-item:not(:disabled)'));
            const all = document.getElementById('pick_all');
            const count = document.getElementById('pick_count');
            const submit = document.getElementById('pick_submit');
            if (!count) return;
            function update() {
                const n = items.filter((i) => i.checked).length;
                count.textContent = n;
                submit.disabled = n === 0;
                all.checked = n > 0 && n === items.length;
            }
            items.forEach((i) => i.addEventListener('change', update));
            all.addEventListener('change', () => { items.forEach((i) => { i.checked = all.checked; }); update(); });
            update();
        })();
    </script>
@endsection
