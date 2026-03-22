<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->merge([
            'nickname' => strtolower(trim($request->nickname))
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'nickname' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'unique:users,nickname',
                'regex:/^(?!.*\.\.)(?!.*\.$)(?!^\.)[a-z0-9._]+$/'
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email'
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults()
            ],

            'birth_date' => ['required', 'date', 'before:today'],
            'city_id' => ['required', 'exists:cities,id'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'nickname' => $request->nickname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'birth_date' => $request->birth_date,
            'city_id' => $request->city_id,
        ]);

        event(new Registered($user));

        $token = $user->createToken('react')->plainTextToken;

        return response()->json([
            'message' => 'Utente registrato correttamente',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required']
        ]);

        $login = strtolower(trim($request->login));

        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'nickname';

        $user = \App\Models\User::where($fieldType, $login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {

            return response()->json([
                'message' => 'Credenziali non valide'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Devi verificare la tua email prima di accedere'
            ], 403);
        }

        // crea token Sanctum
        $token = $user->createToken('react')->plainTextToken;

        // 🔥 LOG LOGIN API
        UserLogin::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
            'logged_in_at' => now(),
        ]);

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        // ✅ VALIDAZIONE
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        // 🔐 CONTROLLO PASSWORD ATTUALE
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'La password attuale non è corretta.'
            ], 422);
        }

        // ✅ AGGIORNA PASSWORD
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password aggiornata con successo.'
        ]);
    }
}
