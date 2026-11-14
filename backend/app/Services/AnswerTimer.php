<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Calcolo del tempo di risposta a prova di manomissione.
 *
 * Il client misura il tempo nel browser e lo invia, ma da solo non e'
 * affidabile: chi invia `time_taken: 0` otterrebbe il bonus velocita' massimo
 * su ogni domanda e un `total_time` di 0, che e' il criterio di spareggio
 * delle classifiche.
 *
 * Qui il valore del client viene accettato solo se NON e' piu' veloce di
 * quanto il server abbia realmente osservato tra un evento e l'altro. Il
 * giocatore onesto mantiene la misura precisa del browser (che tiene conto di
 * quando la domanda e' comparsa a schermo), mentre chi dichiara un tempo
 * inferiore al reale si ritrova assegnato il proprio tempo effettivo.
 *
 * Il Midalario non usa questa classe: essendo un evento sincronizzato, ricava
 * gia' il tempo dalla finestra temporale condivisa della domanda.
 */
class AnswerTimer
{
    /**
     * Margine che assorbe la pausa di 250ms tra una domanda e l'altra piu' la
     * latenza di rete, cosi' il giocatore onesto non viene mai penalizzato.
     * Concede a un eventuale imbroglione solo questi millisecondi.
     */
    private const GRACE_MS = 400;

    /**
     * @param  int|null  $clientTimeMs  tempo dichiarato dal client
     * @param  Carbon|null  $lastEventAt  ultimo istante osservato dal server per
     *                                    questo tentativo (risposta precedente,
     *                                    o inizio del tentativo alla prima domanda)
     * @param  int  $maxTimeMs  limite di tempo della domanda
     */
    /**
     * Tolleranza oltre il limite della domanda prima di considerare una risposta
     * "fuori tempo": copre le pause tra una domanda e l'altra (spiegazioni dei
     * minigiochi fino a 7 s), il caricamento dei media e la rete.
     */
    public const LATE_TOLERANCE_MS = 15000;

    /**
     * Risposta arrivata molto oltre il tempo della domanda (es. chi si ferma a
     * cercare la soluzione): va trattata come tempo scaduto, non come risposta.
     */
    public static function isLate(?Carbon $lastEventAt, int $maxTimeMs): bool
    {
        if (! $lastEventAt || $maxTimeMs <= 0) {
            return false;
        }

        return $lastEventAt->diffInMilliseconds(now()) > $maxTimeMs + self::LATE_TOLERANCE_MS;
    }

    public static function resolve(?int $clientTimeMs, ?Carbon $lastEventAt, int $maxTimeMs): int
    {
        $claimedMs = max(0, (int) $clientTimeMs);

        if (! $lastEventAt) {
            return (int) min($claimedMs, $maxTimeMs);
        }

        $observedMs = max(0, (int) $lastEventAt->diffInMilliseconds(now()) - self::GRACE_MS);

        return (int) min(max($claimedMs, $observedMs), $maxTimeMs);
    }
}
