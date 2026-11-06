<?php

namespace App\Services;

use App\Models\User;
use App\Models\XpEvent;
use Illuminate\Support\Facades\DB;

/**
 * Accredito XP e calcolo livelli. Gli XP sono un sistema parallelo ai punteggi
 * di classifica: ogni gioco ha un massimo configurato in config/xp.php e
 * l'XP dipende dalla percentuale di punteggio ottenuta.
 */
class XpService
{
    /**
     * XP guadagnati da una partita, in base al rapporto punteggio/massimo.
     */
    public static function fromScore(string $source, int $score, int $maxScore): int
    {
        $max = (int) config("xp.games.{$source}", 0);
        $floor = (float) config('xp.floor');

        $ratio = $maxScore > 0 ? min(1, max(0, $score / $maxScore)) : 0;

        return (int) round($max * ($floor + (1 - $floor) * $ratio));
    }

    /**
     * Accredita XP una sola volta per (utente, fonte, id fonte). Ritorna
     * null se gia' accreditati (o XP a zero), altrimenti gli XP assegnati
     * piu' lo stato aggiornato del livello.
     */
    public function award(int $userId, string $source, int $sourceId, int $xp): ?array
    {
        if ($xp <= 0) {
            return null;
        }

        return DB::transaction(function () use ($userId, $source, $sourceId, $xp) {
            $inserted = XpEvent::insertOrIgnore([
                'user_id' => $userId,
                'source' => $source,
                'source_id' => $sourceId,
                'xp' => $xp,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                return null;
            }

            $before = (int) User::whereKey($userId)->lockForUpdate()->value('xp_total');
            User::whereKey($userId)->increment('xp_total', $xp);

            $oldLevel = self::info($before)['level'];
            $info = self::info($before + $xp);

            // Monete: una quota degli XP (stessa fonte, quindi una volta sola)
            // piu' il bonus di ogni livello raggiunto e i regali di livello.
            $avatar = app(AvatarService::class);
            $coins = (int) round($xp * (float) config('xp.coins_per_xp', 0));
            $coinsAwarded = $avatar->addCoins($userId, $source, $sourceId, $coins) ? $coins : 0;
            $unlocked = collect();

            if ($info['level'] > $oldLevel) {
                for ($level = $oldLevel + 1; $level <= $info['level']; $level++) {
                    $bonus = AvatarService::levelUpCoins($level);
                    if ($avatar->addCoins($userId, 'level', $level, $bonus)) {
                        $coinsAwarded += $bonus;
                    }
                }

                $unlocked = $avatar->grantLevelItems($userId, $info['level']);
            }

            $coinsTotal = (int) User::whereKey($userId)->value('coins');

            return [
                'awarded' => $xp,
                // Le monete viaggiano anche dentro `xp`, che le schermate di fine partita ricevono gia'.
                'xp' => $info + ['coins_awarded' => $coinsAwarded, 'coins' => $coinsTotal],
                'coins_awarded' => $coinsAwarded,
                'coins' => $coinsTotal,
                'level_up' => $info['level'] > $oldLevel,
                'unlocked' => $unlocked->map->toCatalog()->values()->all(),
            ];
        });
    }

    /**
     * XP totali necessari per raggiungere un livello (il livello 1 parte da 0).
     */
    public static function xpForLevel(int $level): int
    {
        $base = (int) config('xp.level_base');
        $step = (int) config('xp.level_step');
        $steps = max(0, $level - 1);

        return $base * $steps + $step * intdiv($steps * ($steps + 1), 2);
    }

    public static function info(int $totalXp): array
    {
        $level = 1;
        while (self::xpForLevel($level + 1) <= $totalXp) {
            $level++;
        }

        $currentFloor = self::xpForLevel($level);
        $nextFloor = self::xpForLevel($level + 1);
        $span = max(1, $nextFloor - $currentFloor);

        return [
            'total' => $totalXp,
            'level' => $level,
            'level_start' => $currentFloor,
            'next_level_at' => $nextFloor,
            'into_level' => $totalXp - $currentFloor,
            'level_span' => $span,
            'remaining' => $nextFloor - $totalXp,
            'progress' => round(($totalXp - $currentFloor) / $span * 100, 1),
        ];
    }
}
