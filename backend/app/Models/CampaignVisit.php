<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/** Aperture dei link di campagna, in forma aggregata (campagna + giorno). Nessun dato personale. */
class CampaignVisit extends Model
{
    protected $fillable = ['campaign', 'day', 'hits'];

    // Solo la data (senza ora): la riga del giorno si ritrova con una semplice uguaglianza.
    protected $casts = ['day' => 'date:Y-m-d'];

    /** Solo etichette di campagna "clienti" o "clienti_<settore>": nient'altro puo' essere contato. */
    public static function isValidCampaign(string $campaign): bool
    {
        return (bool) preg_match('/^clienti(_[a-z0-9_.-]{1,40})?$/', $campaign);
    }

    /** Aggiunge un'apertura per la campagna nel giorno di oggi (ora italiana). */
    public static function record(string $campaign): void
    {
        $day = now()->toDateString();

        $updated = static::where('campaign', $campaign)->where('day', $day)->increment('hits');
        if ($updated > 0) {
            return;
        }

        try {
            static::create(['campaign' => $campaign, 'day' => $day, 'hits' => 1]);
        } catch (QueryException) {
            // Un'altra richiesta l'ha creata nello stesso istante: si somma a quella.
            static::where('campaign', $campaign)->where('day', $day)->increment('hits');
        }
    }
}
