<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyLoginBonus;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Honeypot: campo invisibile per gli utenti reali, spesso compilato dai bot.
        if ($request->filled('website')) {
            return response()->json([
                'message' => 'Utente registrato correttamente',
            ], 201);
        }

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

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults()
            ],

            'birth_date' => ['required', 'date', 'before:-14 years'],
            'city_id' => ['required', 'exists:cities,id'],
            'privacy_accepted' => ['accepted'],
            'rules_accepted' => ['accepted'],
        ], [
            'birth_date.before' => 'Devi avere almeno 14 anni per registrarti.',
            'rules_accepted.accepted' => 'Devi accettare la dichiarazione su età, regolamento e privacy.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'nickname' => $request->nickname,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'birth_date' => $request->birth_date,
            'city_id' => $request->city_id,
            'privacy_accepted_at' => now(),
            'terms_accepted_at' => now(),
            'rules_accepted_at' => now(),
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'Utente registrato correttamente',
            'user' => $user->load('city'),
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

        $user = User::where($fieldType, $login)->first();

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

        $token = $user->createToken('react')->plainTextToken;

        UserLogin::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
            'logged_in_at' => now(),
        ]);

        DailyLoginBonus::insertOrIgnore([
            'user_id' => $user->id,
            'bonus_date' => now()->toDateString(),
            'score' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'user' => $user->load(['city', 'latestMonthlyBadge']),
            'token' => $token
        ]);
    }

    public function user(Request $request)
    {
        return response()->json(
            $request->user()->load(['city', 'latestMonthlyBadge'])
        );
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'La password attuale non è corretta.'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password aggiornata con successo.'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['required', 'date', 'before:today'],
            'city_id' => ['required', 'exists:cities,id'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profilo aggiornato correttamente',
            'user' => $user->fresh()->load(['city', 'latestMonthlyBadge']),
        ]);
    }
}
