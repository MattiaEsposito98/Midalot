<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = strtolower(trim($request->login));

        // Capisce se è email o nickname
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'nickname';

        $user = \App\Models\User::where($fieldType, $login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'login' => 'Credenziali non valide.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        // 🔥 LOG DEL LOGIN
        UserLogin::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
            'logged_in_at' => now(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
