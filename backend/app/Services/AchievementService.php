<?php

namespace App\Services;

use App\Models\AvatarItem;
use App\Models\User;
use App\Models\UserAvatarItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Obiettivi: progressi calcolati dai dati che esistono gia' (eventi XP,
 * sfide, acquisti), sblocco una sola volta e consegna dei premi.
 */
class AchievementService
{
    /** @var array<string, int> metriche gia' calcolate in questa richiesta */
    private array $cache = [];

    public static function all(): array
    {
        return config('achievements', []);
    }

    public function metric(int $userId, string $metric): int
    {
        $key = $userId.':'.$metric;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $xpCount = fn (string|array $sources) => (int) DB::table('xp_events')
            ->where('user_id', $userId)
            ->whereIn('source', (array) $sources)
            ->count();

        $value = match ($metric) {
            'challenges_played' => (int) DB::table('challenge_attempts')->where('user_id', $userId)->where('completed', true)->count(),
            'challenges_won' => (int) DB::table('challenges')->where('winner_id', $userId)->count(),
            'login_streak' => $this->bestLoginStreak($userId),
            'quiz', 'minigioco', 'training', 'midalario' => $xpCount($metric),
            'games' => $xpCount(['quiz', 'minigioco', 'training', 'midalario']) + $this->metric($userId, 'challenges_played'),
            'shop_purchases' => (int) UserAvatarItem::where('user_id', $userId)->where('source', 'shop')->count(),
            default => 0,
        };

        return $this->cache[$key] = $value;
    }

    /**
     * Record di giorni consecutivi con accesso (bonus giornaliero), ora italiana.
     */
    private function bestLoginStreak(int $userId): int
    {
        $days = DB::table('xp_events')
            ->where('user_id', $userId)
            ->where('source', 'login')
            ->orderBy('created_at')
            ->pluck('created_at')
            ->map(fn ($at) => Carbon::parse($at)->timezone(config('app.timezone'))->toDateString())
            ->unique()
            ->values();

        $best = 0;
        $run = 0;
        $prev = null;

        foreach ($days as $day) {
            $run = $prev && Carbon::parse($prev)->addDay()->toDateString() === $day ? $run + 1 : 1;
            $best = max($best, $run);
            $prev = $day;
        }

        return $best;
    }

    /**
     * Sblocca gli obiettivi raggiunti e consegna i premi (monete e oggetto).
     *
     * @return array<int, array> obiettivi appena raggiunti
     */
    public function check(int $userId): array
    {
        $done = DB::table('user_achievements')->where('user_id', $userId)->pluck('key')->flip();
        $avatar = app(AvatarService::class);
        $unlocked = [];

        foreach (self::all() as $key => $def) {
            if (isset($done[$key]) || $this->metric($userId, $def['metric']) < $def['target']) {
                continue;
            }

            DB::transaction(function () use ($userId, $key, $def, $avatar, &$unlocked) {
                $now = now();
                $inserted = DB::table('user_achievements')->insertOrIgnore([
                    'user_id' => $userId,
                    'key' => $key,
                    'achieved_at' => $now,
                    'seen_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($inserted === 0) {
                    return;
                }

                $rowId = (int) DB::table('user_achievements')->where('user_id', $userId)->where('key', $key)->value('id');

                if (($def['coins'] ?? 0) > 0) {
                    $avatar->addCoins($userId, 'achievement', $rowId, (int) $def['coins']);
                }

                if (! empty($def['item']) && ($item = AvatarItem::where('key', $def['item'])->first())) {
                    UserAvatarItem::firstOrCreate(
                        ['user_id' => $userId, 'avatar_item_id' => $item->id],
                        // L'avviso dell'obiettivo mostra gia' il premio: niente doppio "nuovo oggetto".
                        ['source' => 'achievement', 'seen_at' => $now]
                    );
                }

                $unlocked[] = $this->present($key, $def, $now);
            });
        }

        return $unlocked;
    }

    /**
     * Tutti gli obiettivi con progresso, per la pagina "Obiettivi".
     */
    public function listFor(User $user): array
    {
        $rows = DB::table('user_achievements')->where('user_id', $user->id)->get()->keyBy('key');

        return collect(self::all())->map(function ($def, $key) use ($user, $rows) {
            $value = $this->metric($user->id, $def['metric']);
            $row = $rows[$key] ?? null;

            return [
                ...$this->present($key, $def, $row?->achieved_at),
                'progress' => min($value, $def['target']),
                'target' => $def['target'],
                'done' => (bool) $row,
            ];
        })->values()->all();
    }

    /** @return array<int, array> obiettivi raggiunti di cui l'utente non ha visto l'avviso */
    public function unseen(User $user): array
    {
        $defs = self::all();

        return DB::table('user_achievements')
            ->where('user_id', $user->id)
            ->whereNull('seen_at')
            ->orderBy('achieved_at')
            ->get()
            ->filter(fn ($row) => isset($defs[$row->key]))
            ->map(fn ($row) => $this->present($row->key, $defs[$row->key], $row->achieved_at))
            ->values()
            ->all();
    }

    public function markSeen(User $user): void
    {
        DB::table('user_achievements')->where('user_id', $user->id)->whereNull('seen_at')->update(['seen_at' => now()]);
    }

    /**
     * Oggetto esclusivo -> nome dell'obiettivo che lo regala (per il guardaroba).
     */
    public static function itemSources(): array
    {
        return collect(self::all())
            ->filter(fn ($def) => ! empty($def['item']))
            ->mapWithKeys(fn ($def) => [$def['item'] => $def['name'].': '.lcfirst($def['description'])])
            ->all();
    }

    private function present(string $key, array $def, $achievedAt): array
    {
        $item = ! empty($def['item']) ? AvatarItem::where('key', $def['item'])->first(['key', 'name', 'slot', 'rarity']) : null;

        return [
            'key' => $key,
            'name' => $def['name'],
            'description' => $def['description'],
            'icon' => $def['icon'] ?? 'bi-award',
            'coins' => (int) ($def['coins'] ?? 0),
            'item' => $item ? ['key' => $item->key, 'name' => $item->name, 'slot' => $item->slot, 'rarity' => $item->rarity] : null,
            'achieved_at' => $achievedAt ? Carbon::parse($achievedAt)->toIso8601String() : null,
        ];
    }
}
