<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {

            return response()->json([
                'message' => 'Credenziali non valide'
            ], 401);
        }

        $user = Auth::user();

        $token = $user->createToken('react')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
