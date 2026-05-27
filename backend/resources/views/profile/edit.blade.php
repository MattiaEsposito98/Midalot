@extends(Auth::user()->is_admin ? 'layouts.admin' : 'layouts.app')

@section('title', 'Profilo')
@section('kicker', 'Account')
@section('page-title', 'Profilo')

@section('content')
    <div class="{{ Auth::user()->is_admin ? 'd-grid gap-3' : 'container py-4' }}">
        @unless (Auth::user()->is_admin)
            <h2 class="fs-4 text-secondary mb-4">Profilo</h2>
        @endunless

        <section class="admin-card">
            <div class="admin-card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-body">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </section>
    </div>
@endsection
