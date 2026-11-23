<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignVisit;
use Illuminate\Http\Request;

/**
 * Conta in modo anonimo le aperture dei link di campagna (midalot.live/ig/...).
 * Non salva IP, user agent, cookie o altro: solo campagna + giorno + numero di aperture.
 * Unica eccezione: se chi apre e' collegato al sito come admin o come utente del team
 * (config campaigns.excluded_nicknames), l'apertura non viene contata. Di lui non si salva nulla.
 */
class CampaignVisitController extends Controller
{
    public function store(Request $request)
    {
        $campaign = (string) $request->input('campaign', '');

        // Etichette non valide e aperture del team vengono ignorate in silenzio.
        if (CampaignVisit::isValidCampaign($campaign) && ! $this->isTeamMember($request)) {
            CampaignVisit::record($campaign);
        }

        return response()->json([], 204);
    }

    /** Admin o utente del team, riconosciuto dal token del sito (se c'e'); nessun dato viene conservato. */
    private function isTeamMember(Request $request): bool
    {
        $user = $request->bearerToken() ? auth('sanctum')->user() : null;
        if (! $user) {
            return false;
        }

        return (bool) $user->is_admin
            || in_array(mb_strtolower((string) $user->nickname), config('campaigns.excluded_nicknames', []), true);
    }
}
