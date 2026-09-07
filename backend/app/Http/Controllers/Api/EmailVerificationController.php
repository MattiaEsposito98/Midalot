<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Il link e' firmato ma la validita' non e' delegata al middleware
     * 'signed': serve gestire a mano i tre esiti (link non valido, gia'
     * verificato, scaduto) per mostrare all'utente una pagina utile invece
     * del 403 generico di Laravel quando il link e' scaduto (dura 60 minuti,
     * vedi User::sendEmailVerificationNotification).
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);
        $frontend = rtrim(config('app.frontend_url'), '/');

        if (! hash_equals((string) $hash, sha1($user->email))) {
            abort(403, 'Link non valido');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect("{$frontend}/verifica-email?stato=gia-verificato");
        }

        if (! URL::hasValidSignature($request)) {
            return redirect("{$frontend}/verifica-email?stato=scaduto&id={$user->id}");
        }

        $user->markEmailAsVerified();

        return redirect("{$frontend}/login?verified=1");
    }

    /**
     * Rinvia il link di verifica. Risposta generica in ogni caso (account
     * inesistente, gia' verificato, o rinviato davvero): non deve rivelare
     * quali id corrispondono a un account reale.
     */
    public function resend(Request $request)
    {
        $request->validate(['id' => ['required', 'integer']]);

        $user = User::find($request->integer('id'));

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Se l\'account esiste e non è già verificato, ti abbiamo inviato un nuovo link.',
        ]);
    }
}
