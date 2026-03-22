<!DOCTYPE html>
<html>

<body style="margin:0; padding:0; background:#f4f6fb; font-family:Arial, sans-serif;">

    <div
        style="max-width:520px; margin:40px auto; background:#ffffff; border-radius:12px; padding:30px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">

        <!-- LOGO -->
        <div style="text-align:center; margin-bottom:25px;">
            <img src="{{ config('app.url') }}/images/Midalot.png" style="height:40px;">
        </div>

        @yield('content')

    </div>

</body>

</html>
