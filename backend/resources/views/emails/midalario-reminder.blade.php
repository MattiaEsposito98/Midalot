@extends('emails.layouts.base')

@section('content')
    @php
        $inizio = $quiz->midalario_scheduled_at->locale('it');
    @endphp

    <h2 style="text-align:center; color:#1e293b; margin-bottom:6px;">
        Il Midalario sta per iniziare 🎙️
    </h2>

    <p style="text-align:center; color:#475569; margin-top:0;">
        Ciao {{ $user->nickname }}, ti sei iscritto a <strong>{{ $quiz->title }}</strong>.
    </p>

    <div style="text-align:center; background:#fff8e1; border:1px solid #ffe08a; border-radius:10px; padding:16px; margin:24px 0;">
        <div style="font-size:13px; color:#8a6d00; text-transform:uppercase; letter-spacing:1px;">Si parte</div>
        <div style="font-size:22px; font-weight:bold; color:#1e293b; margin-top:4px;">
            {{ \Illuminate\Support\Str::ucfirst($inizio->translatedFormat('l j F')) }} alle {{ $inizio->format('H:i') }}
        </div>
    </div>

    <p style="color:#1e293b; font-weight:bold; margin-bottom:8px;">Come funziona</p>

    <ol style="color:#475569; padding-left:20px; line-height:1.6; margin-top:0;">
        <li><strong>Entra in sala d'attesa qualche minuto prima</strong>, dal pulsante qui sotto o da Midalot &rarr; Il Midalario. Accedi con il tuo account.</li>
        <li><strong>Tieni la pagina aperta</strong>: quando le iscrizioni si chiudono e il quiz parte, le domande compaiono da sole.</li>
        <li><strong>Le domande sono in diretta e a tempo</strong>, uguali per tutti nello stesso momento. Più sei veloce a rispondere giusto, più punti fai.</li>
        <li><strong>Se arrivi in ritardo</strong> puoi ancora giocare, ma le domande già passate non si recuperano.</li>
        <li><strong>Alla fine</strong> puoi rivedere le tue risposte e scoprire come ti sei piazzato in classifica.</li>
    </ol>

    <div style="text-align:center; margin:30px 0;">
        <a href="{{ $roomUrl }}" target="_blank"
            style="display:inline-block; background:#6366f1; color:white; padding:14px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">
            Vai alla sala d'attesa
        </a>
    </div>

    <p style="text-align:center; color:#94a3b8; font-size:12px; margin-bottom:0;">
        Ricevi questa email perché ti sei iscritto a questo Midalario su Midalot.
    </p>
@endsection
