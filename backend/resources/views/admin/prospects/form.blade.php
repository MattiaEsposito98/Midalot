@extends('layouts.admin')

@php
    use App\Models\Prospect;
    $isEdit = $prospect->exists;
@endphp

@section('title', $isEdit ? 'Modifica profilo' : 'Nuovo profilo')
@section('kicker', 'Clienti da contattare')
@section('page-title', $isEdit ? 'Modifica profilo' : 'Nuovo profilo')
@section('activeNav', 'prospects')

@section('content')
    @if ($isEdit)
        <section class="admin-card mb-3">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-section-title">Messaggio per questo profilo</h2>
                    <p class="admin-muted mb-0">Bozza costruita sui dati del profilo: ritoccala come vuoi prima di copiarla (le modifiche qui non vengono salvate). Il messaggio lo invii tu, a mano.</p>
                </div>
            </div>
            <div class="admin-card-body">
                <textarea id="outreach-message" class="form-control mb-2" rows="9">{{ $prospect->outreach_message }}</textarea>
                <div class="admin-page-actions">
                    <button type="button" class="btn btn-success" data-copy-message="" data-copy-source="outreach-message" data-open-url="{{ $prospect->instagram_chat_url }}">
                        <i class="bi bi-clipboard"></i>
                        Copia e apri chat
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-copy-message="" data-copy-source="outreach-message">
                        <i class="bi bi-clipboard"></i>
                        Solo copia
                    </button>
                    <a href="{{ $prospect->instagram_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">
                        <i class="bi bi-instagram"></i>
                        Apri profilo
                    </a>
                </div>
                <p class="small admin-muted mt-2 mb-0">Dopo l'invio, segna lo stato "Contattato" (e se serve un promemoria) qui sotto o dall'elenco.</p>
            </div>
        </section>
    @endif

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-section-title">{{ $isEdit ? '@' . $prospect->instagram_handle : 'Aggiungi un profilo' }}</h2>
                <p class="admin-muted mb-0">
                    @if ($isEdit)
                        <a href="{{ $prospect->instagram_url }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-instagram"></i> Apri il profilo su Instagram</a>
                    @else
                        Inserisci solo ciò che l'attività mostra pubblicamente nel profilo.
                    @endif
                </p>
            </div>
            <div class="admin-page-actions">
                <a href="{{ route('admin.prospects.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Torna all'elenco
                </a>
            </div>
        </div>

        <div class="admin-card-body">
            <div class="alert alert-light border small">
                <i class="bi bi-shield-check"></i>
                <strong>Privacy.</strong> Solo dati pubblici di attività, niente profili di privati né dati sensibili.
                Molte attività sono ditte individuali (dati personali): tieni al minimo.
                Email e telefono solo se l'attività li mostra pubblicamente nel profilo, da usare per messaggi singoli e personali.
            </div>

            <form action="{{ $isEdit ? route('admin.prospects.update', $prospect) : route('admin.prospects.store') }}" method="POST">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="admin-form-grid">
                    <div>
                        <label class="form-label" for="instagram_handle">Profilo Instagram</label>
                        <input type="text" id="instagram_handle" name="instagram_handle" class="form-control" required maxlength="120"
                            value="{{ old('instagram_handle', $prospect->instagram_handle) }}" placeholder="nomeprofilo (anche @nome o link)">
                        <small class="admin-muted">Si salva in minuscolo, senza @. Deve essere unico.</small>
                    </div>

                    <div>
                        <label class="form-label" for="name">Nome dell'attività</label>
                        <input type="text" id="name" name="name" class="form-control" maxlength="150" value="{{ old('name', $prospect->name) }}">
                    </div>

                    <div>
                        <label class="form-label" for="followers">Follower</label>
                        <input type="number" id="followers" name="followers" class="form-control" min="0" value="{{ old('followers', $prospect->followers) }}">
                    </div>

                    <div>
                        <label class="form-label" for="region">Regione</label>
                        <select id="region" name="region" class="form-select">
                            <option value="">-</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region }}" @selected(old('region', $prospect->region) === $region)>{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="city">Città</label>
                        <input type="text" id="city" name="city" class="form-control" maxlength="80" value="{{ old('city', $prospect->city) }}">
                    </div>

                    <div>
                        <label class="form-label" for="sector">Settore</label>
                        <select id="sector" name="sector" class="form-select">
                            <option value="">-</option>
                            @foreach ($sectors as $sector)
                                <option value="{{ $sector }}" @selected(old('sector', $prospect->sector) === $sector)>{{ $sector }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="full">
                        <label class="form-label" for="bio">Descrizione del profilo (bio)</label>
                        <textarea id="bio" name="bio" class="form-control" rows="3" maxlength="2000">{{ old('bio', $prospect->bio) }}</textarea>
                    </div>

                    <div>
                        <label class="form-label" for="website_url">Sito web o link in bio</label>
                        <input type="text" id="website_url" name="website_url" class="form-control" maxlength="255" value="{{ old('website_url', $prospect->website_url) }}" placeholder="https://...">
                        <small class="admin-muted">Solo indirizzi http/https.</small>
                    </div>

                    <div>
                        <label class="form-label" for="website_status">Situazione del sito</label>
                        <select id="website_status" name="website_status" class="form-select" required>
                            @foreach (Prospect::WEBSITE_STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('website_status', $prospect->website_status ?: 'unknown') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="email">Email (solo se pubblica)</label>
                        <input type="email" id="email" name="email" class="form-control" maxlength="190" value="{{ old('email', $prospect->email) }}">
                    </div>

                    <div>
                        <label class="form-label" for="phone">Telefono (solo se pubblico)</label>
                        <input type="text" id="phone" name="phone" class="form-control" maxlength="40" value="{{ old('phone', $prospect->phone) }}">
                    </div>

                    @php
                        $triState = fn ($field) => (string) old($field, $prospect->$field === null ? '' : (int) $prospect->$field);
                    @endphp
                    <div>
                        <label class="form-label" for="has_physical_store">Negozio fisico</label>
                        <select id="has_physical_store" name="has_physical_store" class="form-select">
                            <option value="" @selected($triState('has_physical_store') === '')>Non so</option>
                            <option value="1" @selected($triState('has_physical_store') === '1')>Sì</option>
                            <option value="0" @selected($triState('has_physical_store') === '0')>No</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="orders_in_dm">Prende ordini in messaggio privato</label>
                        <select id="orders_in_dm" name="orders_in_dm" class="form-select">
                            <option value="" @selected($triState('orders_in_dm') === '')>Non so</option>
                            <option value="1" @selected($triState('orders_in_dm') === '1')>Sì</option>
                            <option value="0" @selected($triState('orders_in_dm') === '0')>No</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="activity">Attività del profilo</label>
                        <select id="activity" name="activity" class="form-select" required>
                            @foreach (Prospect::ACTIVITIES as $key => $label)
                                <option value="{{ $key }}" @selected(old('activity', $prospect->activity ?: 'unverified') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="admin-muted">"Attivo" solo dopo aver controllato l'ultimo post.</small>
                    </div>

                    <div>
                        <label class="form-label" for="proposal">Proposta consigliata</label>
                        <select id="proposal" name="proposal" class="form-select">
                            <option value="">-</option>
                            @foreach (Prospect::PROPOSALS as $key => $label)
                                <option value="{{ $key }}" @selected(old('proposal', $prospect->proposal) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="full">
                        <label class="form-label" for="signals">Segnali (cosa vende, "ordina in DM", ecc.)</label>
                        <textarea id="signals" name="signals" class="form-control" rows="2" maxlength="2000">{{ old('signals', $prospect->signals) }}</textarea>
                    </div>

                    <div>
                        <label class="form-label" for="priority">Priorità</label>
                        <select id="priority" name="priority" class="form-select" required>
                            @foreach (Prospect::PRIORITIES as $key => $label)
                                <option value="{{ $key }}" @selected(old('priority', $prospect->priority ?: 'B') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="prospect_search_id">Ricerca di provenienza</label>
                        <select id="prospect_search_id" name="prospect_search_id" class="form-select">
                            <option value="">-</option>
                            @foreach ($searches as $search)
                                <option value="{{ $search->id }}" @selected((string) old('prospect_search_id', $prospect->prospect_search_id) === (string) $search->id)>{{ $search->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="status">Stato del contatto</label>
                        <select id="status" name="status" class="form-select" required>
                            @foreach (Prospect::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $prospect->status ?: 'to_contact') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($prospect->contacted_at)
                            <small class="admin-muted">
                                Primo contatto il {{ $prospect->contacted_at->format('d/m/Y') }}{{ $prospect->contactedBy ? ' da ' . $prospect->contactedBy->name : '' }}.
                            </small>
                        @else
                            <small class="admin-muted">Data e autore del contatto si salvano quando lo stato esce da "Da contattare".</small>
                        @endif
                    </div>

                    <div>
                        <label class="form-label" for="follow_up_at">Promemoria: ricontattare entro</label>
                        <input type="date" id="follow_up_at" name="follow_up_at" class="form-control"
                            value="{{ old('follow_up_at', optional($prospect->follow_up_at)->format('Y-m-d')) }}">
                        <small class="admin-muted">Si azzera da solo se il profilo diventa "Cliente" o "Non idoneo".</small>
                    </div>

                    <div class="full">
                        <label class="form-label" for="notes">Note</label>
                        <textarea id="notes" name="notes" class="form-control" rows="4" maxlength="5000">{{ old('notes', $prospect->notes) }}</textarea>
                    </div>
                </div>

                <div class="admin-page-actions mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i>
                        {{ $isEdit ? 'Salva modifiche' : 'Aggiungi profilo' }}
                    </button>
                    <a href="{{ route('admin.prospects.index') }}" class="btn btn-outline-secondary">Annulla</a>
                </div>
            </form>

            @if ($isEdit)
                <hr>
                <form action="{{ route('admin.prospects.destroy', $prospect) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm"
                        onclick="return confirm('Eliminare definitivamente questo profilo dall\'elenco?')">
                        <i class="bi bi-trash"></i>
                        Elimina profilo (richiesta di rimozione)
                    </button>
                </form>
            @endif
        </div>
    </section>

    @if ($isEdit)
        @include('admin.prospects._copy-script')
    @endif

    @include('admin.prospects._city-autocomplete', ['cityId' => 'city', 'regionId' => 'region'])
@endsection
