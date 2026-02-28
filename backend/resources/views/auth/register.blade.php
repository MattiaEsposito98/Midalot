@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold">
                        {{ __('Register') }}
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <!-- NAME -->
                            <div class="mb-4 row">
                                <label for="name" class="col-md-4 col-form-label text-md-end">
                                    {{ __('Name') }}
                                </label>

                                <div class="col-md-6">
                                    <input id="name" type="text"
                                        class="form-control @error('name') is-invalid @enderror" name="name"
                                        value="{{ old('name') }}" required autocomplete="name" autofocus>

                                    @error('name')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- ✅ DATA DI NASCITA -->
                            <div class="mb-4 row">
                                <label for="birth_date" class="col-md-4 col-form-label text-md-end">
                                    Data di nascita
                                </label>

                                <div class="col-md-6">
                                    <input id="birth_date" type="date"
                                        class="form-control @error('birth_date') is-invalid @enderror" name="birth_date"
                                        value="{{ old('birth_date') }}" required>

                                    @error('birth_date')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- ✅ CITTÀ -->
                            <div class="mb-4 row position-relative">
                                <label class="col-md-4 col-form-label text-md-end">
                                    Città
                                </label>

                                <div class="col-md-6">
                                    <input type="text" id="city_search"
                                        class="form-control @error('city_id') is-invalid @enderror"
                                        placeholder="Scrivi la tua città" autocomplete="off" required autocomplete="off">

                                    <input type="hidden" name="city_id" id="city_id" value="{{ old('city_id') }}">

                                    <div id="city_results" class="list-group position-absolute w-100" style="z-index:1000;">
                                    </div>

                                    @error('city_id')
                                        <span class="invalid-feedback d-block">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- NICKNAME -->
                            <div class="mb-4 row">
                                <label for="nickname" class="col-md-4 col-form-label text-md-end">
                                    Nickname
                                </label>

                                <div class="col-md-6">
                                    <input id="nickname" type="text"
                                        class="form-control @error('nickname') is-invalid @enderror" name="nickname"
                                        value="{{ old('nickname') }}" required maxlength="30"
                                        pattern="^(?!.*\.\.)(?!.*\.$)(?!^\.)[a-z0-9._]+$"
                                        oninput="this.value = this.value.replace(/\s/g, '').toLowerCase();"
                                        placeholder="es. mario.rossi_12">

                                    <small class="text-muted">
                                        Usa il tuo nickname Instagram se possibile.
                                    </small><br>

                                    <small class="text-muted">
                                        3–30 caratteri tutti in minuscolo. Solo lettere, numeri, _ e . <br>
                                        Non può iniziare o finire con punto e non può contenere ".."
                                    </small>

                                    @error('nickname')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- EMAIL -->
                            <div class="mb-4 row">
                                <label for="email" class="col-md-4 col-form-label text-md-end">
                                    {{ __('E-Mail Address') }}
                                </label>

                                <div class="col-md-6">
                                    <input id="email" type="email"
                                        class="form-control @error('email') is-invalid @enderror" name="email"
                                        value="{{ old('email') }}" required autocomplete="email">

                                    @error('email')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- PASSWORD -->
                            <div class="mb-4 row">
                                <label for="password" class="col-md-4 col-form-label text-md-end">
                                    {{ __('Password') }}
                                </label>

                                <div class="col-md-6">
                                    <input id="password" type="password"
                                        class="form-control @error('password') is-invalid @enderror" name="password"
                                        required autocomplete="new-password">

                                    @error('password')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- CONFIRM PASSWORD -->
                            <div class="mb-4 row">
                                <label for="password-confirm" class="col-md-4 col-form-label text-md-end">
                                    {{ __('Confirm Password') }}
                                </label>

                                <div class="col-md-6">
                                    <input id="password-confirm" type="password" class="form-control"
                                        name="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>

                            <!-- SUBMIT -->
                            <div class="mb-0 row">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-primary px-4">
                                        {{ __('Register') }}
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPT AUTOCOMPLETE --}}
    <script>
        const input = document.getElementById('city_search');
        const resultsBox = document.getElementById('city_results');
        const hiddenCity = document.getElementById('city_id');

        let debounce;

        input.addEventListener('input', function() {

            hiddenCity.value = '';

            clearTimeout(debounce);

            debounce = setTimeout(async () => {

                const q = this.value;

                if (q.length < 2) {
                    resultsBox.innerHTML = '';
                    return;
                }

                const response = await fetch(`/cities/search?q=${q}`);
                const cities = await response.json();

                resultsBox.innerHTML = '';

                cities.forEach(city => {

                    const item = document.createElement('button');
                    item.type = "button";
                    item.classList.add('list-group-item', 'list-group-item-action');
                    item.textContent = city.name;

                    item.onclick = () => {
                        input.value = city.name;
                        hiddenCity.value = city.id;
                        resultsBox.innerHTML = '';
                    };

                    resultsBox.appendChild(item);
                });

            }, 300);
        });

        document.querySelector("form").addEventListener("submit", function(e) {
            if (!hiddenCity.value) {
                e.preventDefault();
                alert("Seleziona una città valida dalla lista.");
            }
        });
    </script>
@endsection
