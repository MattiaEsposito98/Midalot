<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
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
                'lowercase',
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

            // ✅ NUOVI CAMPI
            'birth_date' => ['required', 'date', 'before:today'],
            'city_id' => ['required', 'exists:cities,id'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'nickname' => $request->nickname,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'birth_date' => $request->birth_date,
            'city_id' => $request->city_id,
        ]);

        event(new Registered($user));

        return redirect()->route('login')->with('status', 'Registrazione completata! Controlla la tua email per verificare l’account.');
    }
}
