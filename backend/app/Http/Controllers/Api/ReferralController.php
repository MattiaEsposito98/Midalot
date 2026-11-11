<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InviteFriendMail;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Invita un amico: link personale, amici invitati e invito via email.
 */
class ReferralController extends Controller
{
    public function __construct(private ReferralService $referrals) {}

    public function show(Request $request)
    {
        return response()->json($this->referrals->summary($request->user()));
    }

    /**
     * Invito via email. L'indirizzo serve solo per questo invio: si conserva
     * solo il suo hash, per i limiti e per non reinvitare la stessa persona.
     */
    public function email(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $user = $request->user();
        $email = strtolower(trim($data['email']));
        $hash = hash('sha256', $email);

        if (strcasecmp($email, $user->email) === 0) {
            return response()->json(['message' => 'Non puoi invitare te stesso 😄'], 422);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'Questa persona è già su Midalot: sfidala dalla pagina Sfide!'], 422);
        }

        $today = DB::table('invite_emails')->where('user_id', $user->id)->where('created_at', '>=', now()->subDay())->count();
        if ($today >= (int) config('referral.emails_per_day', 5)) {
            return response()->json(['message' => 'Hai già mandato '.$today.' inviti oggi. Riprova domani, o condividi il link su WhatsApp!'], 429);
        }

        $recent = DB::table('invite_emails')->where('email_hash', $hash)
            ->where('created_at', '>=', now()->subDays((int) config('referral.same_email_days', 7)))->exists();
        if ($recent) {
            return response()->json(['message' => 'Questa persona ha già ricevuto un invito da poco.'], 422);
        }

        Mail::to($email)->send(new InviteFriendMail($user, $this->referrals->link($user)));

        DB::table('invite_emails')->insert([
            'user_id' => $user->id,
            'email_hash' => $hash,
            'kind' => 'friend',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Invito inviato! Riceverai le monete quando il tuo amico inizierà a giocare.']);
    }
}
