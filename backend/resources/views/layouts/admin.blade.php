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
            @endphp

            <nav class="admin-nav" aria-label="Menu amministrazione">
                <a href="{{ route('admin.index') }}" class="admin-nav-link {{ Route::is('admin.index') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.midalario.index') }}" class="admin-nav-link {{ Route::is('admin.midalario.*') || $activeNav === 'midalario' ? 'active' : '' }}">
                    <i class="bi bi-broadcast"></i>
                    <span>Il Midalario</span>
                </a>
                <a href="{{ route('admin.quizzes.index') }}" class="admin-nav-link {{ Route::is('admin.quizzes.*') && $activeNav !== 'midalario' && $activeNav !== 'training' ? 'active' : '' }}">
                    <i class="bi bi-ui-checks-grid"></i>
                    <span>Quiz</span>
                </a>
                <a href="{{ route('admin.training.quizzes.index') }}" class="admin-nav-link {{ Route::is('admin.training.*') || $activeNav === 'training' ? 'active' : '' }}">
                    <i class="bi bi-lightning-charge"></i>
                    <span>Training</span>
                </a>
                <a href="{{ route('admin.reports.index') }}" class="admin-nav-link {{ Route::is('admin.reports.*') ? 'active' : '' }}">
                    <i class="bi bi-flag"></i>
                    <span>Segnalazioni</span>
                    @if ($openReportsCount > 0)
                        <span class="admin-nav-count">{{ $openReportsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.showcase.index') }}" class="admin-nav-link {{ Route::is('admin.showcase.*') ? 'active' : '' }}">
                    <i class="bi bi-images"></i>
                    <span>Vetrina</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="admin-nav-link">
                    <i class="bi bi-person-circle"></i>
                    <span>Profilo</span>
                </a>
            </nav>

            <div class="admin-topnav-right">
                <div class="admin-user">
                    <span>{{ Auth::user()->name }}</span>
                    <small>Admin</small>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="admin-logout">
                    @csrf
                    <button type="submit" class="admin-nav-link admin-nav-button">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </button>
                </form>
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
