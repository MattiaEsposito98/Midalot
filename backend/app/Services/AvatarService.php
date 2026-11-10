<?php

namespace App\Services;

use App\Models\AvatarItem;
use App\Models\CoinEvent;
use App\Models\User;
use App\Models\UserAvatar;
use App\Models\UserAvatarItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Avatar: oggetti posseduti, sblocchi a livello, monete e negozio.
 */
class AvatarService
{
    /**
     * Movimento di monete, una sola volta per (utente, fonte, id fonte).
     * Ritorna false se era gia' stato registrato.
     */
    public function addCoins(int $userId, string $source, int $sourceId, int $amount): bool
    {
        if ($amount === 0) {
            return false;
        }

        $inserted = CoinEvent::insertOrIgnore([
            'user_id' => $userId,
            'source' => $source,
            'source_id' => $sourceId,
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 0) {
            return false;
        }

        if ($amount > 0) {
            User::whereKey($userId)->increment('coins', $amount);
        } else {
            User::whereKey($userId)->decrement('coins', -$amount);
        }

        return true;
    }

    /** @var array{frames: array<string, true>, titles: array<string, string>}|null */
    private static ?array $looks = null;

    /**
     * Cornice e titolo da mostrare agli altri. Oggetti disattivati o
     * inesistenti diventano null. Una sola query per richiesta.
     */
    public static function look(?string $frame, ?string $title): array
    {
        if (self::$looks === null) {
            $items = AvatarItem::where('is_active', true)->whereIn('slot', ['frame', 'title'])->get(['key', 'name', 'slot']);
            self::$looks = [
                'frames' => $items->where('slot', 'frame')->mapWithKeys(fn ($i) => [$i->key => true])->all(),
                'titles' => $items->where('slot', 'title')->pluck('name', 'key')->all(),
            ];
        }

        return [
            'avatar_frame' => $frame && isset(self::$looks['frames'][$frame]) ? $frame : null,
            'avatar_title' => $title ? (self::$looks['titles'][$title] ?? null) : null,
        ];
    }

    public static function levelUpCoins(int $level): int
    {
        return (int) config('xp.level_up_coins', 0) + (int) config('xp.level_up_coins_step', 0) * $level;
    }

    /**
     * Regala gli oggetti previsti fino a $level che l'utente non ha ancora.
     * Vale anche per gli oggetti aggiunti al catalogo in seguito.
     *
     * @return Collection<int, AvatarItem> oggetti appena regalati
     */
    public function grantLevelItems(int $userId, int $level): Collection
    {
        $owned = UserAvatarItem::where('user_id', $userId)->pluck('avatar_item_id');

        $items = AvatarItem::where('is_active', true)
            ->whereNotNull('unlock_level')
            ->where('unlock_level', '<=', $level)
            ->whereNotIn('id', $owned)
            ->orderBy('unlock_level')
            ->orderBy('sort_order')
            ->get();

        if ($items->isEmpty()) {
            return $items;
        }

        $now = now();

        UserAvatarItem::insertOrIgnore($items->map(fn (AvatarItem $item) => [
            'user_id' => $userId,
            'avatar_item_id' => $item->id,
            'source' => 'level',
            // Il kit base non merita un avviso.
            'seen_at' => $item->unlock_level <= 1 ? $now : null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        return $items->filter(fn (AvatarItem $item) => $item->unlock_level > 1)->values();
    }

    public function syncUnlocks(User $user): Collection
    {
        return $this->grantLevelItems($user->id, XpService::info((int) $user->xp_total)['level']);
    }

    /**
     * @return string[] key degli oggetti posseduti
     */
    public function ownedKeys(User $user): array
    {
        $keys = AvatarItem::query()
            ->join('user_avatar_items', 'user_avatar_items.avatar_item_id', '=', 'avatar_items.id')
            ->where('user_avatar_items.user_id', $user->id)
            ->where('avatar_items.is_active', true)
            ->pluck('avatar_items.key')
            ->all();

        // Chi aveva un vecchio oggetto-colore (es. "Gonna blu") possiede l'oggetto con tavolozza.
        $legacy = self::legacyMap();
        foreach ($this->legacyOwned($user) as $old) {
            $keys[] = $legacy[$old][0];
        }

        return array_values(array_unique($keys));
    }

    // ------------------------------------------------------------ colori (tavolozze)

    /**
     * Vecchio oggetto separato -> [oggetto con tavolozza, colore].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function legacyMap(): array
    {
        $map = [];
        foreach (config('avatar.palettes', []) as $itemKey => $colors) {
            foreach ($colors as [$color, , $old]) {
                if ($old) {
                    $map[$old] = [$itemKey, $color];
                }
            }
        }

        return $map;
    }

    /**
     * Vecchi oggetti-colore posseduti (anche se ora sono disattivati).
     *
     * @return string[]
     */
    public function legacyOwned(User $user): array
    {
        $legacy = array_keys(self::legacyMap());
        if (! $legacy) {
            return [];
        }

        return AvatarItem::query()
            ->join('user_avatar_items', 'user_avatar_items.avatar_item_id', '=', 'avatar_items.id')
            ->where('user_avatar_items.user_id', $user->id)
            ->whereIn('avatar_items.key', $legacy)
            ->pluck('avatar_items.key')
            ->all();
    }

    /**
     * Colori di ogni oggetto con tavolozza e se l'utente li ha sbloccati.
     *
     * @return array<string, array<int, array{key: string, level: int, unlocked: bool}>>
     */
    public function palettesFor(User $user): array
    {
        $level = XpService::info((int) $user->xp_total)['level'];
        $legacyOwned = array_flip($this->legacyOwned($user));
        $out = [];

        foreach (config('avatar.palettes', []) as $itemKey => $colors) {
            foreach ($colors as [$color, $minLevel, $old]) {
                $out[$itemKey][] = [
                    'key' => $color,
                    'level' => $minLevel,
                    'unlocked' => $level >= $minLevel || ($old && isset($legacyOwned[$old])),
                ];
            }
        }

        return $out;
    }

    /**
     * Tiene solo i colori validi e sbloccati degli oggetti posseduti.
     */
    private function cleanColors(array $itemColors, array $palettes, array $owned): array
    {
        $clean = [];

        foreach ($itemColors as $itemKey => $color) {
            if (! isset($owned[$itemKey], $palettes[$itemKey]) || ! is_string($color)) {
                continue;
            }

            foreach ($palettes[$itemKey] as $option) {
                if ($option['key'] === $color && $option['unlocked']) {
                    $clean[$itemKey] = $color;
                }
            }
        }

        return $clean;
    }

    /**
     * Configurazione dell'avatar (salvata o di partenza). Gli oggetti indossati
     * che l'utente non possiede piu' (es. disattivati) vengono tolti.
     */
    public function config(User $user, ?array $ownedKeys = null): array
    {
        $saved = UserAvatar::where('user_id', $user->id)->value('config');
        $saved = is_string($saved) ? json_decode($saved, true) : ($saved ?? []);

        $config = array_merge(config('avatar.default'), $saved ?: []);
        $equipped = array_merge(config('avatar.base_items'), $saved['equipped'] ?? []);
        $itemColors = is_array($saved['itemColors'] ?? null) ? $saved['itemColors'] : [];

        // Avatar salvati prima delle tavolozze: "maglietta_rossa" -> maglietta + colore rosso.
        $legacy = self::legacyMap();
        foreach ($equipped as $slot => $key) {
            if (isset($legacy[$key])) {
                [$newKey, $color] = $legacy[$key];
                $equipped[$slot] = $newKey;
                $itemColors[$newKey] ??= $color;
            }
        }

        $owned = array_flip($ownedKeys ?? $this->ownedKeys($user));
        foreach ($equipped as $slot => $key) {
            if (! isset($owned[$key])) {
                if (isset(config('avatar.base_items')[$slot])) {
                    $equipped[$slot] = config('avatar.base_items')[$slot];
                } else {
                    unset($equipped[$slot]);
                }
            }
        }

        $config['equipped'] = $equipped;
        $config['itemColors'] = (object) $this->cleanColors($itemColors, $this->palettesFor($user), $owned);

        return $config;
    }

    /**
     * Salva aspetto e oggetti indossati (gia' validati nel formato).
     */
    public function save(User $user, array $data): array
    {
        $equipped = array_filter($data['equipped'] ?? [], fn ($key) => is_string($key) && $key !== '');

        $items = AvatarItem::whereIn('key', array_values($equipped))->where('is_active', true)->get()->keyBy('key');
        $owned = array_flip($this->ownedKeys($user));

        foreach ($equipped as $slot => $key) {
            $item = $items[$key] ?? null;

            if (! $item || $item->slot !== $slot) {
                throw ValidationException::withMessages(['equipped' => "Oggetto non valido: {$key}"]);
            }

            if (! isset($owned[$key])) {
                throw ValidationException::withMessages(['equipped' => "Non possiedi ancora: {$item->name}"]);
            }
        }

        foreach (AvatarItem::REQUIRED_SLOTS as $slot) {
            $equipped[$slot] ??= config('avatar.base_items')[$slot];
        }

        $config = [
            'gender' => $data['gender'],
            'skin' => $data['skin'],
            'faceShape' => $data['faceShape'],
            'brows' => $data['brows'],
            'mouth' => $data['mouth'],
            'beard' => $data['gender'] === 'f' ? 'nessuna' : $data['beard'],
            'freckles' => (bool) $data['freckles'],
            'hair' => $data['hair'],
            'hairColor' => $data['hairColor'],
            'eyes' => $data['eyes'],
            'emblem' => $data['emblem'],
            'equipped' => $equipped,
            'itemColors' => $this->cleanColors($data['itemColors'] ?? [], $this->palettesFor($user), $owned),
        ];

        UserAvatar::updateOrCreate(['user_id' => $user->id], ['config' => $config]);

        // Cornice e titolo si copiano sull'utente: cosi' sfide e classifiche li
        // mostrano senza leggere la configurazione completa dell'avatar.
        $user->forceFill([
            'avatar_frame' => $equipped['frame'] ?? null,
            'avatar_title' => $equipped['title'] ?? null,
        ])->save();

        return $this->config($user);
    }

    /**
     * Acquisto nel negozio.
     */
    public function buy(User $user, AvatarItem $item): void
    {
        DB::transaction(function () use ($user, $item) {
            $fresh = User::whereKey($user->id)->lockForUpdate()->first();
            $level = XpService::info((int) $fresh->xp_total)['level'];

            if (! $item->is_active || $item->price === null) {
                throw ValidationException::withMessages(['item' => 'Questo oggetto non è in vendita.']);
            }

            if ($level < $item->shop_min_level) {
                throw ValidationException::withMessages(['item' => "Serve il livello {$item->shop_min_level} per comprarlo."]);
            }

            if (UserAvatarItem::where('user_id', $user->id)->where('avatar_item_id', $item->id)->exists()) {
                throw ValidationException::withMessages(['item' => 'Hai già questo oggetto.']);
            }

            if ($fresh->coins < $item->price) {
                throw ValidationException::withMessages(['item' => 'Monete insufficienti.']);
            }

            $owned = UserAvatarItem::create([
                'user_id' => $user->id,
                'avatar_item_id' => $item->id,
                'source' => 'shop',
                'seen_at' => now(),
            ]);

            // Il pagamento e' legato a questo acquisto (non all'oggetto): se l'admin
            // toglie l'oggetto e l'utente lo ricompra, paga di nuovo.
            if ($item->price > 0) {
                $this->addCoins($user->id, 'shop', $owned->id, -$item->price);
            }
        });
    }

    /**
     * Oggetti sbloccati di cui l'utente non ha ancora visto l'avviso.
     */
    public function unseen(User $user): Collection
    {
        return AvatarItem::query()
            ->join('user_avatar_items', 'user_avatar_items.avatar_item_id', '=', 'avatar_items.id')
            ->where('user_avatar_items.user_id', $user->id)
            ->whereNull('user_avatar_items.seen_at')
            ->where('avatar_items.is_active', true)
            ->orderBy('avatar_items.unlock_level')
            ->orderBy('avatar_items.sort_order')
            ->get(['avatar_items.*']);
    }

    public function markSeen(User $user): void
    {
        UserAvatarItem::where('user_id', $user->id)->whereNull('seen_at')->update(['seen_at' => now()]);
    }

    public function catalog(): Collection
    {
        return AvatarItem::where('is_active', true)
            ->orderByRaw('unlock_level IS NULL')
            ->orderBy('unlock_level')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (AvatarItem $item) => $item->toCatalog());
    }
}
