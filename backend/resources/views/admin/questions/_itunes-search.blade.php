@php
    $hasItunesAudio = isset($question) && $question->audio_source === 'itunes' && $question->itunes_preview_url;
    $existingStart = old('audio_start_seconds', $question->audio_start_seconds ?? '');
    $existingEnd = old('audio_end_seconds', $question->audio_end_seconds ?? '');
@endphp

<div id="audioItunesBlock" style="display: none;">
    <div class="input-group input-group-sm mb-2">
        <input type="text" id="itunesSearchInput" class="form-control" placeholder="Cerca canzone o artista...">
        <button type="button" id="itunesSearchBtn" class="btn btn-outline-primary">
            <i class="bi bi-search"></i>
            Cerca
        </button>
    </div>

    <div id="itunesResults" class="list-group mb-2" style="max-height: 260px; overflow-y: auto;"></div>

    <div id="itunesSelectedWrap" class="itunes-selected-wrap {{ $hasItunesAudio ? '' : 'd-none' }}">
        <div class="itunes-selected-title mb-2">
            <i class="bi bi-music-note-beamed"></i>
            <span id="itunesSelectedLabel">
                @if ($hasItunesAudio)
                    <strong>{{ $question->itunes_track_name }}</strong> &mdash; {{ $question->itunes_artist_name }}
                @endif
            </span>
        </div>

        <audio id="itunesPlayer" controls preload="metadata" class="w-100 mb-2"
            @if ($hasItunesAudio) src="{{ $question->resolvedAudioUrl() }}" @endif></audio>

        <div class="itunes-range">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small fw-bold">Intervallo da usare nella domanda</span>
                <button type="button" id="itunesRangeReset" class="btn btn-link btn-sm p-0 text-decoration-none">
                    Usa tutto il brano
                </button>
            </div>

            <div class="itunes-slider" id="itunesSlider">
                <div class="itunes-slider-track"></div>
                <div class="itunes-slider-fill" id="itunesSliderFill"></div>
                <input type="range" id="itunesRangeStart" min="0" max="30" step="0.1" value="0">
                <input type="range" id="itunesRangeEnd" min="0" max="30" step="0.1" value="30">
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                <div class="small admin-muted">
                    Da <strong id="itunesRangeStartLabel">0:00.0</strong>
                    a <strong id="itunesRangeEndLabel">0:30.0</strong>
                    <span id="itunesRangeDuration" class="badge bg-secondary ms-1">30,0 s</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="itunesPlayRange" class="btn btn-sm btn-primary">
                        <i class="bi bi-play-fill"></i>
                        Ascolta selezione
                    </button>
                    <button type="button" id="itunesPlayAll" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-soundwave"></i>
                        Ascolta tutto
                    </button>
                    <button type="button" id="itunesStop" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-stop-fill"></i>
                        Ferma
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="itunesEmptyHint" class="admin-muted small {{ $hasItunesAudio ? 'd-none' : '' }}">
        Nessuna canzone selezionata.
    </div>

    <input type="hidden" name="itunes_track_id" id="itunesTrackId" value="{{ old('itunes_track_id', $question->itunes_track_id ?? '') }}">
    <input type="hidden" name="itunes_track_name" id="itunesTrackName" value="{{ old('itunes_track_name', $question->itunes_track_name ?? '') }}">
    <input type="hidden" name="itunes_artist_name" id="itunesArtistName" value="{{ old('itunes_artist_name', $question->itunes_artist_name ?? '') }}">
    <input type="hidden" name="itunes_preview_url" id="itunesPreviewUrl" value="{{ old('itunes_preview_url', $question->itunes_preview_url ?? '') }}">
    <input type="hidden" name="audio_start_seconds" id="audioStartSeconds" value="{{ $existingStart }}">
    <input type="hidden" name="audio_end_seconds" id="audioEndSeconds" value="{{ $existingEnd }}">
</div>

@once
    @push('styles')
        <style>
            .itunes-selected-wrap {
                border: 1px solid #dce5ee;
                border-radius: 8px;
                background: #f8fbff;
                padding: 12px;
            }

            .itunes-selected-title {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 0.92rem;
            }

            .itunes-slider {
                position: relative;
                height: 28px;
            }

            .itunes-slider-track,
            .itunes-slider-fill {
                position: absolute;
                top: 12px;
                height: 5px;
                border-radius: 3px;
                pointer-events: none;
            }

            .itunes-slider-track {
                left: 0;
                right: 0;
                background: #d7e2ee;
            }

            .itunes-slider-fill {
                background: #ffc107;
            }

            .itunes-slider input[type="range"] {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 28px;
                margin: 0;
                background: none;
                appearance: none;
                -webkit-appearance: none;
                pointer-events: none;
            }

            .itunes-slider input[type="range"]::-webkit-slider-thumb {
                pointer-events: auto;
                appearance: none;
                -webkit-appearance: none;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #182033;
                border: 3px solid #ffffff;
                box-shadow: 0 1px 4px rgba(24, 32, 51, 0.4);
                cursor: pointer;
            }

            .itunes-slider input[type="range"]::-moz-range-thumb {
                pointer-events: auto;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #182033;
                border: 3px solid #ffffff;
                box-shadow: 0 1px 4px rgba(24, 32, 51, 0.4);
                cursor: pointer;
            }

            .itunes-slider input[type="range"]::-moz-range-track {
                background: none;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                const searchUrl = @json(route('admin.itunes.search'));
                const audioProxyUrl = @json(route('audio.proxy'));

                function escapeHtml(str) {
                    const div = document.createElement('div');
                    div.textContent = str ?? '';
                    return div.innerHTML;
                }

                function proxiedAudioSrc(previewUrl) {
                    return audioProxyUrl + '?url=' + encodeURIComponent(previewUrl);
                }

                function formatTime(seconds) {
                    const total = Math.max(0, Number(seconds) || 0);
                    const mins = Math.floor(total / 60);
                    const secs = Math.floor(total % 60);
                    const tenths = Math.floor((total * 10) % 10);
                    return mins + ':' + String(secs).padStart(2, '0') + '.' + tenths;
                }

                document.querySelectorAll('form').forEach((form) => {
                    const uploadRadio = form.querySelector('#audioSourceUpload');
                    const itunesRadio = form.querySelector('#audioSourceItunes');

                    if (!uploadRadio || !itunesRadio) return;

                    const uploadBlock = form.querySelector('#audioUploadBlock');
                    const itunesBlock = form.querySelector('#audioItunesBlock');
                    const searchInput = form.querySelector('#itunesSearchInput');
                    const searchBtn = form.querySelector('#itunesSearchBtn');
                    const resultsEl = form.querySelector('#itunesResults');
                    const selectedWrap = form.querySelector('#itunesSelectedWrap');
                    const selectedLabel = form.querySelector('#itunesSelectedLabel');
                    const emptyHint = form.querySelector('#itunesEmptyHint');
                    const player = form.querySelector('#itunesPlayer');

                    const trackIdInput = form.querySelector('#itunesTrackId');
                    const trackNameInput = form.querySelector('#itunesTrackName');
                    const artistNameInput = form.querySelector('#itunesArtistName');
                    const previewUrlInput = form.querySelector('#itunesPreviewUrl');
                    const startInput = form.querySelector('#audioStartSeconds');
                    const endInput = form.querySelector('#audioEndSeconds');

                    const rangeStart = form.querySelector('#itunesRangeStart');
                    const rangeEnd = form.querySelector('#itunesRangeEnd');
                    const sliderFill = form.querySelector('#itunesSliderFill');
                    const startLabel = form.querySelector('#itunesRangeStartLabel');
                    const endLabel = form.querySelector('#itunesRangeEndLabel');
                    const durationBadge = form.querySelector('#itunesRangeDuration');
                    const playRangeBtn = form.querySelector('#itunesPlayRange');
                    const playAllBtn = form.querySelector('#itunesPlayAll');
                    const stopBtn = form.querySelector('#itunesStop');
                    const resetBtn = form.querySelector('#itunesRangeReset');

                    const MIN_SPAN = 1;
                    let limitPlaybackToRange = true;

                    function syncAudioBlocks() {
                        const selected = form.querySelector('input[name="audio_source"]:checked')?.value || 'upload';
                        uploadBlock.style.display = selected === 'upload' ? '' : 'none';
                        itunesBlock.style.display = selected === 'itunes' ? '' : 'none';

                        if (selected !== 'itunes') {
                            player.pause();
                        }
                    }

                    if (previewUrlInput.value) {
                        itunesRadio.checked = true;
                    }

                    uploadRadio.addEventListener('change', syncAudioBlocks);
                    itunesRadio.addEventListener('change', syncAudioBlocks);
                    syncAudioBlocks();

                    function refreshRangeUi() {
                        const max = Number(rangeEnd.max) || 30;
                        const start = Number(rangeStart.value);
                        const end = Number(rangeEnd.value);

                        const leftPct = (start / max) * 100;
                        const widthPct = ((end - start) / max) * 100;
                        sliderFill.style.left = leftPct + '%';
                        sliderFill.style.width = widthPct + '%';

                        startLabel.textContent = formatTime(start);
                        endLabel.textContent = formatTime(end);
                        durationBadge.textContent = (end - start).toFixed(1).replace('.', ',') + ' s';

                        startInput.value = start.toFixed(2);
                        endInput.value = end.toFixed(2);
                    }

                    function setSliderBounds(duration) {
                        const max = Math.max(1, Math.round((duration || 30) * 10) / 10);
                        rangeStart.max = max;
                        rangeEnd.max = max;

                        const savedStart = parseFloat(startInput.value);
                        const savedEnd = parseFloat(endInput.value);
                        const hasSaved = Number.isFinite(savedStart) && Number.isFinite(savedEnd) && savedEnd > savedStart;

                        rangeStart.value = hasSaved ? Math.min(savedStart, max - MIN_SPAN) : 0;
                        rangeEnd.value = hasSaved ? Math.min(savedEnd, max) : max;

                        refreshRangeUi();
                    }

                    rangeStart.addEventListener('input', () => {
                        const max = Number(rangeEnd.max);
                        let start = Number(rangeStart.value);
                        const end = Number(rangeEnd.value);

                        if (start > end - MIN_SPAN) {
                            start = Math.max(0, Math.min(end - MIN_SPAN, max - MIN_SPAN));
                            rangeStart.value = start;
                        }

                        refreshRangeUi();
                    });

                    rangeEnd.addEventListener('input', () => {
                        const max = Number(rangeEnd.max);
                        let end = Number(rangeEnd.value);
                        const start = Number(rangeStart.value);

                        if (end < start + MIN_SPAN) {
                            end = Math.min(max, start + MIN_SPAN);
                            rangeEnd.value = end;
                        }

                        refreshRangeUi();
                    });

                    resetBtn.addEventListener('click', () => {
                        rangeStart.value = 0;
                        rangeEnd.value = rangeEnd.max;
                        refreshRangeUi();
                    });

                    player.addEventListener('loadedmetadata', () => {
                        if (Number.isFinite(player.duration)) {
                            setSliderBounds(player.duration);
                        }
                    });

                    player.addEventListener('timeupdate', () => {
                        if (!limitPlaybackToRange) return;

                        const end = Number(rangeEnd.value);
                        if (player.currentTime >= end) {
                            player.pause();
                            player.currentTime = Number(rangeStart.value);
                        }
                    });

                    playRangeBtn.addEventListener('click', () => {
                        limitPlaybackToRange = true;
                        player.currentTime = Number(rangeStart.value);
                        player.play();
                    });

                    playAllBtn.addEventListener('click', () => {
                        limitPlaybackToRange = false;
                        player.currentTime = 0;
                        player.play();
                    });

                    stopBtn.addEventListener('click', () => {
                        player.pause();
                        limitPlaybackToRange = true;
                        player.currentTime = Number(rangeStart.value);
                    });

                    // Un solo brano alla volta: se ne parte uno, gli altri si fermano.
                    itunesBlock.addEventListener('play', (e) => {
                        itunesBlock.querySelectorAll('audio').forEach((other) => {
                            if (other !== e.target) other.pause();
                        });
                    }, true);

                    function selectTrack(track) {
                        trackIdInput.value = track.trackId;
                        trackNameInput.value = track.trackName;
                        artistNameInput.value = track.artistName;
                        previewUrlInput.value = track.previewUrl;

                        // Nuovo brano: il range va ricalcolato da zero.
                        startInput.value = '';
                        endInput.value = '';

                        selectedLabel.innerHTML = '<strong>' + escapeHtml(track.trackName) + '</strong> &mdash; ' + escapeHtml(track.artistName);
                        selectedWrap.classList.remove('d-none');
                        emptyHint.classList.add('d-none');

                        limitPlaybackToRange = true;
                        player.src = proxiedAudioSrc(track.previewUrl);
                        player.load();
                        setSliderBounds(30);

                        selectedWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }

                    function renderResults(results) {
                        if (!results.length) {
                            resultsEl.innerHTML = '<div class="p-2 small admin-muted">Nessun risultato.</div>';
                            return;
                        }

                        resultsEl.innerHTML = '';

                        results.forEach((track) => {
                            const item = document.createElement('div');
                            item.className = 'list-group-item d-flex align-items-center gap-2';
                            // preload="none": senza questo ogni risultato scaricherebbe subito
                            // l'intero estratto, saturando il server e lasciando i player a 0:00.
                            item.innerHTML = '<img src="' + (track.artworkUrl ?? '') + '" alt="" style="width:40px;height:40px;border-radius:4px;object-fit:cover;flex-shrink:0;">' +
                                '<div class="flex-grow-1" style="min-width:0;">' +
                                '<div class="fw-bold small text-truncate">' + escapeHtml(track.trackName) + '</div>' +
                                '<div class="small admin-muted text-truncate">' + escapeHtml(track.artistName) + '</div>' +
                                '</div>' +
                                '<audio controls preload="none" src="' + proxiedAudioSrc(track.previewUrl) + '" style="height:32px;width:170px;flex-shrink:0;"></audio>' +
                                '<button type="button" class="btn btn-sm btn-primary flex-shrink-0">Seleziona</button>';

                            item.querySelector('button').addEventListener('click', () => selectTrack(track));
                            resultsEl.appendChild(item);
                        });
                    }

                    async function runSearch() {
                        const term = searchInput.value.trim();
                        if (!term) return;

                        resultsEl.innerHTML = '<div class="p-2 small admin-muted">Ricerca in corso...</div>';

                        try {
                            const res = await fetch(searchUrl + '?term=' + encodeURIComponent(term));
                            const data = await res.json();
                            renderResults(data.results || []);
                        } catch (err) {
                            resultsEl.innerHTML = '<div class="p-2 small text-danger">Errore nella ricerca.</div>';
                        }
                    }

                    searchBtn.addEventListener('click', runSearch);
                    searchInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            runSearch();
                        }
                    });

                    // Stato iniziale in modifica: se c'e' gia' un brano, prepara lo slider.
                    if (previewUrlInput.value) {
                        setSliderBounds(30);
                    }
                });
            })();
        </script>
    @endpush
@endonce
