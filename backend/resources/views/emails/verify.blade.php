<!DOCTYPE html>
<html>

<body style="margin:0; padding:0; background:#f4f6fb; font-family:Arial, sans-serif;">

    <div
        style="max-width:520px; margin:40px auto; background:#ffffff; border-radius:12px; padding:30px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">

        <!-- LOGO -->
        <div style="text-align:center; margin-bottom:25px;">
            <img src="{{ config('app.url') }}/images/Midalot.png" alt="Midalot" style="height:40px;">
        </div>

        <!-- TITLE -->
        <h2 style="text-align:center; color:#1e293b;">
            Benvenuto su Midalot 🚀
        </h2>

        <!-- TEXT -->
        <p style="color:#475569; text-align:center;">
            Ciao {{ $user->name ?? '' }} 👋
        </p>

        <p style="color:#475569; text-align:center;">
            Grazie per esserti registrato.<br>
            Verifica la tua email per iniziare.
        </p>

        <!-- BUTTON -->
        <div style="text-align:center; margin:30px 0;">
            <a href="{{ $url }}"
                style="background:#6366f1; color:white; padding:14px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">
                Verifica Email
            </a>
        </div>

        <!-- FOOTER -->
        <p style="font-size:12px; color:#94a3b8; text-align:center;">
            Se non hai creato questo account, ignora questa email.
        </p>

    </div>

</body>

</html>
