@php
    $isServizi = ($eventRequest['kind'] ?? 'evento') === 'servizi';
    $titolo = $isServizi ? 'Nuova richiesta servizi digitali' : 'Nuova richiesta quiz personalizzato per evento';
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>{{ $titolo }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #182033; line-height: 1.5;">
    <h1>{{ $titolo }}</h1>

    <p><strong>Nome e cognome:</strong> {{ $eventRequest['name'] }}</p>
    <p><strong>Contatto:</strong> {{ $eventRequest['contact'] }}</p>
    @if ($isServizi)
        <p><strong>Servizio:</strong> {{ $eventRequest['event_type'] ?? 'Non specificato' }}</p>
    @else
        <p><strong>Tipo di evento:</strong> {{ $eventRequest['event_type'] ?? 'Non specificato' }}</p>
        <p><strong>Data prevista:</strong> {{ $eventRequest['event_date'] ?? 'Non specificata' }}</p>
    @endif
    <p><strong>Utente registrato:</strong> {{ $eventRequest['user_email'] ?? 'No - richiesta da ospite' }}</p>

    <h2>Messaggio</h2>
    <p style="white-space: pre-wrap;">{{ $eventRequest['message'] }}</p>
</body>
</html>
