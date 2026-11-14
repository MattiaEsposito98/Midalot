<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Premi in monete per posizione (classifica mensile e Midalario).
 * Le scale stanno in config/prizes.php. Ogni premio e' accreditato una sola
 * volta per (utente, tipo, riferimento): rieseguire l'assegnazione non
 * raddoppia le monete e non ripete l'avviso.
 */
class PrizeService
{
    public function __construct(private AvatarService $avatar)
    {
    }

    /**
     * Posizioni in "competition ranking": i pari merito condividono la
     * posizione e il successivo la salta (due primi -> poi il 3°).
     *
     * @param  array<int, array{user_id:int, key:array}>  $rows  gia' ordinate dal migliore al peggiore;
     *                                                          `key` e' cio' che decide il pari merito
     * @return array<int, array{user_id:int, position:int}>
     */
    public static function rank(array $rows): array
    {
        $ranked = [];
        $position = 0;
        $previousKey = null;

        foreach ($rows as $index => $row) {
            if ($previousKey === null || $row['key'] !== $previousKey) {
                $position = $index + 1;
                $previousKey = $row['key'];
            }

            $ranked[] = ['user_id' => $row['user_id'], 'position' => $position];
        }

        return $ranked;
    }

    public static function coinsFor(string $kind, int $position): int
    {
        $scale = array_values((array) config("prizes.{$kind}", []));

        return (int) ($scale[$position - 1] ?? 0);
    }

    /**
     * @param  array<int, array{user_id:int, position:int}>  $ranked
     * @return array<int, array{user_id:int, position:int, coins:int}> premi appena assegnati
     */
    public function awardRanked(string $kind, string $ref, int $coinSourceId, string $label, array $ranked): array
    {
        $given = [];

        foreach ($ranked as $row) {
            $coins = self::coinsFor($kind, $row['position']);

            if ($coins <= 0) {
                continue;
            }

            $done = DB::transaction(function () use ($kind, $ref, $coinSourceId, $label, $row, $coins) {
                if (! $this->avatar->addCoins($row['user_id'], "{$kind}_prize", $coinSourceId, $coins)) {
                    return false;
                }

                DB::table('prize_awards')->insertOrIgnore([
                    'user_id' => $row['user_id'],
                    'kind' => $kind,
                    'ref' => $ref,
                    'label' => $label,
                    'position' => $row['position'],
                    'coins' => $coins,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return true;
            });

            if ($done) {
                $given[] = $row + ['coins' => $coins];
            }
        }

        return $given;
    }

    /** @return array<int, array> premi di cui l'utente non ha visto l'avviso */
    public function unseen(User $user): array
    {
        return DB::table('prize_awards')
            ->where('user_id', $user->id)
            ->whereNull('seen_at')
            ->orderBy('created_at')
            ->get(['kind', 'ref', 'label', 'position', 'coins'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function markSeen(User $user): void
    {
        DB::table('prize_awards')->where('user_id', $user->id)->whereNull('seen_at')->update(['seen_at' => now()]);
    }
}
