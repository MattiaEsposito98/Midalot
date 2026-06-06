<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Segnalazione domanda training</title>
</head>
<body style="font-family: Arial, sans-serif; color: #182033; line-height: 1.5;">
    <h1>Nuova segnalazione training</h1>

    <p><strong>Nickname:</strong> {{ $report['nickname'] }}</p>
    <p><strong>Email utente:</strong> {{ $report['user_email'] ?: 'Non disponibile - ospite' }}</p>
    <p><strong>Categoria:</strong> {{ $report['category_name'] }} ({{ $report['category_slug'] }})</p>
    <p><strong>Training:</strong> {{ $report['quiz_title'] }} - ID {{ $report['quiz_id'] }}</p>
    <p><strong>Domanda:</strong> {{ $report['question_text'] }} - ID {{ $report['question_id'] }}</p>

    <h2>Messaggio dell'utente</h2>
    <p style="white-space: pre-wrap;">{{ $report['message'] }}</p>
</body>
</html>
