<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {

            // 🔑 genera token reset
            $token = Password::createToken($user);

            // 🔗 crea link frontend
            $url = config('app.frontend_url') . "/reset-password?token={$token}&email={$user->email}";

            // 📩 invia email custom
            Mail::to($user->email)->send(new ResetPasswordMail($url, $user));
        }

        return response()->json([
            'message' => 'Se l\'email esiste, ti abbiamo inviato le istruzioni.'
        ]);
    }
}
