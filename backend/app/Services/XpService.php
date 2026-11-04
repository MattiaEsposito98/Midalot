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

            User::whereKey($userId)->increment('xp_total', $xp);

            return [
                'awarded' => $xp,
                'xp' => self::info((int) User::whereKey($userId)->value('xp_total')),
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
