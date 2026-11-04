@extends('emails.layouts.base')

@section('content')
    <h2 style="text-align:center; color:#1e293b; margin-bottom:6px;">
        {{ $challenge->mode === 'live' ? 'Sfida LIVE in corso ⚡' : 'Hai una nuova sfida ⚔️' }}
    </h2>

    <p style="text-align:center; color:#475569; margin-top:0;">
        Ciao {{ $invitee->nickname }}, <strong>{{ $challenge->challenger->nickname }}</strong> ti ha sfidato a un quiz di {{ \App\Models\Challenge::QUESTIONS_COUNT }} domande{{ $challenge->mode === 'live' ? ', in diretta' : '' }}.
    </p>

    @if (count($otherInvitees) > 0)
        <p style="text-align:center; color:#475569; margin-top:0;">
            Sfida di gruppo: giocherai anche contro <strong>{{ implode(', ', $otherInvitees) }}</strong>.
        </p>
    @endif

    <div style="text-align:center; background:#fff8e1; border:1px solid #ffe08a; border-radius:10px; padding:16px; margin:24px 0;">
        <div style="font-size:13px; color:#8a6d00; text-transform:uppercase; letter-spacing:1px;">{{ $challenge->mode === 'live' ? 'Entra entro le' : 'Hai tempo fino a' }}</div>
        <div style="font-size:22px; font-weight:bold; color:#1e293b; margin-top:4px;">
            @if ($challenge->mode === 'live')
                {{ $challenge->expires_at->format('H:i') }}
            @else
            {{ $challenge->expires_at->copy()->locale('it')->translatedFormat('j F \a\l\l\e H:i') }}
            @endif
        </div>
    </div>

    <p style="color:#475569; line-height:1.6;">
        Più sei veloce a rispondere giusto, più punti fai. Le risposte corrette si vedono solo a sfida conclusa, e puoi guadagnare XP extra.
    </p>

    <div style="text-align:center; margin:30px 0;">
        <a href="{{ $challengeUrl }}" target="_blank"
            style="display:inline-block; background:#6366f1; color:white; padding:14px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">
            Vai alla sfida
        </a>
    </div>

    <p style="text-align:center; color:#94a3b8; font-size:12px; margin-bottom:0;">
        Non vuoi ricevere sfide? Puoi disattivarle dal tuo profilo su Midalot.
    </p>
@endsection
