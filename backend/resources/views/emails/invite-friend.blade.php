@extends('emails.layouts.base')

@section('content')
    <h2 style="text-align:center; color:#1e293b; margin-bottom:6px;">Ti hanno invitato su Midalot 🎉</h2>

    <p style="text-align:center; color:#475569; margin-top:0;">
        <strong>{{ $inviter->nickname }}</strong> gioca su Midalot e vorrebbe sfidarti a colpi di quiz.
    </p>

    <p style="color:#475569; line-height:1.6;">
        Su Midalot ci sono quiz, minigiochi, training e sfide tra amici. Giocando sali di livello,
        guadagni monete e personalizzi il tuo avatar. Registrarsi è gratis e iscrivendoti con questo invito
        ricevi <strong>{{ config('referral.welcome_coins') }} monete di benvenuto</strong>.
    </p>

    <div style="text-align:center; margin:30px 0;">
        <a href="{{ $link }}" target="_blank"
            style="display:inline-block; background:#6366f1; color:white; padding:14px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">
            Accetta l'invito
        </a>
    </div>

    <p style="text-align:center; color:#94a3b8; font-size:12px; margin-bottom:0;">
        Hai ricevuto questa email perché {{ $inviter->nickname }} ha inserito il tuo indirizzo per invitarti.
        Il tuo indirizzo non viene salvato né usato per altro. Se non ti interessa, ignora pure questo messaggio.
    </p>
@endsection
