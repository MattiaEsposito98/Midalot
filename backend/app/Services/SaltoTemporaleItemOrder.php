<?php

namespace App\Services;

use App\Models\MinigiocoRound;

/**
 * Anti-cheat per Salto Temporale.
 *
 * L'ordine corretto di un round e' quello degli item per `ordine` crescente, e
 * poiche' gli item vengono inseriti in sequenza dal pannello admin, l'ordine
 * corretto coincideva sempre con quello degli id crescenti. Dato che gli id
 * veri finivano nel payload di gioco, bastava ordinare gli item ricevuti per
 * id e inviarli per indovinare sempre.
 *
 * Qui gli id esposti al client vengono sostituiti da una permutazione dello
 * stesso insieme di id: il client continua a ricevere e rimandare indietro dei
 * normali interi, ma ordinarli non da' piu' alcun vantaggio. La permutazione e'
 * deterministica (serve a ritradurre la risposta) e dipende da APP_KEY, quindi
 * non e' riproducibile da chi non ha accesso al server.
 *
 * Vale solo per Salto Temporale: Trova l'Intruso valuta il flag `is_intruso`
 * dell'item scelto, quindi l'ordine degli id non rivela nulla.
 */
class SaltoTemporaleItemOrder
{
    /**
     * Id reali nell'ordine corretto, affiancati alla permutazione mostrata.
     *
     * @return array{0: list<int>, 1: list<int>}
     */
    private static function pair(MinigiocoRound $round, int $userId): array
    {
        $realOrder = $round->items->pluck('id')->map(fn ($id) => (int) $id)->all();

        $displayOrder = collect($realOrder)
            ->sortBy(fn ($id) => hash_hmac('sha256', $userId.':'.$round->id.':'.$id, (string) config('app.key')))
            ->values()
            ->all();

        return [$realOrder, $displayOrder];
    }

    /**
     * @return array<int, int> id reale => id da mostrare al client
     */
    public static function realToDisplay(MinigiocoRound $round, int $userId): array
    {
        [$realOrder, $displayOrder] = self::pair($round, $userId);

        return array_combine($realOrder, $displayOrder);
    }

    /**
     * @return array<int, int> id ricevuto dal client => id reale
     */
    public static function displayToReal(MinigiocoRound $round, int $userId): array
    {
        [$realOrder, $displayOrder] = self::pair($round, $userId);

        return array_combine($displayOrder, $realOrder);
    }
}
