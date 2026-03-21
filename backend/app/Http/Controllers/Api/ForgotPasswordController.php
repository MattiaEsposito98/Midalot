<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        // invia email reset
        Password::sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'message' => 'Se l\'email esiste, ti abbiamo inviato le istruzioni.'
        ]);
    }
}
