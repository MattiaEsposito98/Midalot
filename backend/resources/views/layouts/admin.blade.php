<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin') - {{ config('app.name', 'Midalot') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Midalot.png') }}">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    @vite(['resources/js/app.js'])

    @stack('styles')
</head>

<body class="admin-body">
    <div class="admin-shell">
        <header class="admin-topnav">
            <a class="admin-brand" href="{{ route('admin.index') }}">
                <img src="{{ asset('images/Midalot.png') }}" alt="Midalot">
                <span>Midalot Admin</span>
            </a>

            @php
                $activeNav = trim($__env->yieldContent('activeNav'));

                // Menu raggruppato per sezioni: ogni voce = [rotta, pattern attivo, icona, etichetta, contatore].
                $navGroups = [
                    'giochi' => [
                        'label' => 'Giochi',
                        'icon' => 'bi-controller',
                        'items' => [
                            ['admin.midalario.index', Route::is('admin.midalario.*') || $activeNav === 'midalario', 'bi-broadcast', 'Il Midalario', 0],
                            ['admin.quizzes.index', Route::is('admin.quizzes.*') && $activeNav !== 'midalario' && $activeNav !== 'training', 'bi-ui-checks-grid', 'Quiz', 0],
                            ['admin.training.quizzes.index', Route::is('admin.training.*') || $activeNav === 'training', 'bi-lightning-charge', 'Training', 0],
                            ['admin.minigiochi.index', Route::is('admin.minigiochi.*'), 'bi-joystick', 'Minigiochi', 0],
                            ['admin.challenges.index', Route::is('admin.challenges.*'), 'bi-lightning-charge-fill', 'Sfide', 0],
                        ],
                    ],
                    'premi' => [
                        'label' => 'Premi e avatar',
                        'icon' => 'bi-trophy',
                        'items' => [
                            ['admin.period-leaderboard.index', Route::is('admin.period-leaderboard.*'), 'bi-trophy', 'Classifica premi', 0],
                            ['admin.prize-awards.index', Route::is('admin.prize-awards.*'), 'bi-coin', 'Premi in monete', 0],
                            ['admin.avatar.index', Route::is('admin.avatar.*'), 'bi-person-badge', 'Avatar e negozio', 0],
                        ],
                    ],
                    'richieste' => [
                        'label' => 'Richieste',
                        'icon' => 'bi-inbox',
                        'items' => [
                            ['admin.reports.index', Route::is('admin.reports.*'), 'bi-flag', 'Segnalazioni', $openReportsCount],
                            ['admin.event-requests.index', Route::is('admin.event-requests.*'), 'bi-stars', 'Richieste contatto', $newEventRequestsCount],
                            ['admin.prospects.index', Route::is('admin.prospects.*') || Route::is('admin.prospect-searches.*'), 'bi-person-lines-fill', 'Clienti da contattare', $prospectFollowUpsDue],
                            ['admin.showcase.index', Route::is('admin.showcase.*'), 'bi-images', 'Vetrina', 0],
                        ],
                    ],
                ];
            @endphp

            <nav class="admin-nav" aria-label="Menu amministrazione">
                <a href="{{ route('admin.index') }}" class="admin-nav-link {{ Route::is('admin.index') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.question-drafts.index') }}"
                    class="admin-nav-link {{ Route::is('admin.question-drafts.*') ? 'active' : '' }}"
                    title="Domande generate dall'AI in attesa di conferma">
                    <i class="bi bi-patch-question"></i>
                    <span>Domande da confermare</span>
                    @if ($pendingQuestionsCount > 0)
                        <span class="admin-nav-count">{{ $pendingQuestionsCount }}</span>
                    @endif
                </a>

                @foreach ($navGroups as $groupKey => $group)
                    @php
                        $groupActive = collect($group['items'])->contains(fn ($item) => $item[1]);
                        $groupCount = collect($group['items'])->sum(fn ($item) => $item[4]);
                    @endphp
                    <div class="dropdown">
                        <button type="button" class="admin-nav-link admin-nav-button dropdown-toggle {{ $groupActive ? 'active' : '' }}"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi {{ $group['icon'] }}"></i>
                            <span>{{ $group['label'] }}</span>
                            @if ($groupCount > 0)
                                <span class="admin-nav-count">{{ $groupCount }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu admin-nav-menu">
                            @foreach ($group['items'] as [$route, $active, $icon, $label, $count])
                                <li>
                                    <a href="{{ route($route) }}" class="dropdown-item {{ $active ? 'active' : '' }}">
                                        <i class="bi {{ $icon }}"></i>
                                        {{ $label }}
                                        @if ($count > 0)
                                            <span class="admin-nav-count ms-auto">{{ $count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>

            <div class="admin-topnav-right dropdown">
                <button type="button" class="admin-nav-link admin-nav-button admin-user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i>
                    <span class="admin-user">
                        <span>{{ Auth::user()->name }}</span>
                        <small>Admin</small>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end admin-nav-menu">
                    <li>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item">
                            <i class="bi bi-person-circle"></i>
                            Profilo
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="admin-logout">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right"></i>
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <div class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="admin-kicker mb-1">@yield('kicker', 'Area amministrazione')</p>
                    <h1>@yield('page-title', 'Dashboard')</h1>
                </div>
            </header>

            <main class="admin-content">
                @if (session('success'))
                    <div class="alert alert-success admin-alert">
                        {{ session('success') }}
                    </div>
                @endif

                @isset($errors)
                    @if ($errors->any())
                        <div class="alert alert-danger admin-alert">
                            <strong>Controlla i dati inseriti.</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endisset

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
