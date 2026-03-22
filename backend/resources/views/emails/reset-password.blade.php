@extends('emails.layouts.base')

@section('content')
    <h2 style="text-align:center; color:#1e293b;">
        Reset password 🔐
    </h2>

    <p style="text-align:center;">Ciao {{ $user->name ?? '' }} 👋</p>

    <p style="text-align:center;">
        Clicca qui sotto per reimpostare la password.
    </p>

    <div style="text-align:center; margin:30px 0;">
        <a href="{{ $url }}" target="_blank"
            style="display:inline-block; background:#6366f1; color:white; padding:14px 24px; border-radius:8px; text-decoration:none;">
            Reimposta Password
        </a>
    </div>
@endsection
