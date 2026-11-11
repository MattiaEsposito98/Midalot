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
        $items = $this->grantLevelItems($user->id, XpService::info((int) $user->xp_total)['level']);
        $this->grantThemeRewards($user);

        return $items;
    }

    // ------------------------------------------------------------ temi

    /**
     * Temi con i loro oggetti e quanti ne possiede l'utente.
     *
     * @return array<int, array{key: string, name: string, icon: string, reward: ?array, items: string[], owned: int, complete: bool}>
     */
    public function themes(User $user): array
    {
        $themes = config('avatar.themes', []);
        if (! $themes) {
            return [];
        }

        $items = AvatarItem::where('is_active', true)->whereIn('theme', array_keys($themes))
            ->orderBy('sort_order')->get();
        $rewards = AvatarItem::whereIn('key', array_column($themes, 'reward'))->pluck('name', 'key');
        $owned = UserAvatarItem::where('user_id', $user->id)->pluck('avatar_item_id')->flip();

        $out = [];
        foreach ($themes as $key => $def) {
            $list = $items->where('theme', $key);

            // Tema stagionale chiuso: si vede solo se l'utente ne possiede gia' qualcosa.
            $visible = $list->filter(fn (AvatarItem $i) => ! $i->is_exclusive || $i->exclusiveOnSale() || isset($owned[$i->id]));
            if ($visible->isEmpty()) {
                continue;
            }

            $have = $list->filter(fn ($i) => isset($owned[$i->id]))->count();
            $out[] = [
                'key' => $key,
                'name' => $def['name'],
                'icon' => $def['icon'] ?? 'bi-stars',
                'reward' => isset($rewards[$def['reward'] ?? '']) ? ['key' => $def['reward'], 'name' => $rewards[$def['reward']]] : null,
                'items' => $list->pluck('key')->values()->all(),
                'owned' => $have,
                'complete' => $have === $list->count(),
            ];
        }

        return $out;
    }

    /**
     * Regala il titolo del set a chi possiede tutti gli oggetti di un tema.
     */
    public function grantThemeRewards(User $user): void
    {
        foreach ($this->themes($user) as $theme) {
            if (! $theme['complete'] || ! $theme['reward']) {
                continue;
            }

            $reward = AvatarItem::where('key', $theme['reward']['key'])->value('id');
            UserAvatarItem::insertOrIgnore([
                'user_id' => $user->id,
                'avatar_item_id' => $reward,
                'source' => 'theme',
                'seen_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Testo "come si ottiene" dei titoli dei set.
     *
     * @return array<string, string>
     */
    public static function themeRewardSources(): array
    {
        return collect(config('avatar.themes', []))
            ->filter(fn ($def) => ! empty($def['reward']))
            ->mapWithKeys(fn ($def) => [$def['reward'] => 'Completa il tema '.$def['name']])
            ->all();
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

            $price = $this->priceFor($item);

            if (! $item->is_active || $price === null) {
                throw ValidationException::withMessages(['item' => 'Questo oggetto non è in vendita.']);
            }

            if ($level < $item->shop_min_level) {
                throw ValidationException::withMessages(['item' => "Serve il livello {$item->shop_min_level} per comprarlo."]);
            }

            if (UserAvatarItem::where('user_id', $user->id)->where('avatar_item_id', $item->id)->exists()) {
                throw ValidationException::withMessages(['item' => 'Hai già questo oggetto.']);
            }

            if ($fresh->coins < $price) {
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
            if ($price > 0) {
                $this->addCoins($user->id, 'shop', $owned->id, -$price);
            }
        });

        if ($item->theme) {
            $this->grantThemeRewards($user);
        }
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

    // ------------------------------------------------------------ vetrina e prezzi

    /** @var array{discount: int, count: int}|null */
    private ?array $settingsCache = null;

    private ?array $promoCache = null;

    /**
     * Impostazioni di base della vetrina: quelle salvate dall'admin, altrimenti
     * i valori di config/avatar.php.
     *
     * @return array{discount: int, count: int}
     */
    public function settings(): array
    {
        if ($this->settingsCache === null) {
            $saved = DB::table('avatar_settings')->pluck('value', 'key');

            $this->settingsCache = [
                'discount' => (int) ($saved['showcase_discount'] ?? config('avatar.showcase.discount', 30)),
                'count' => (int) ($saved['showcase_count'] ?? config('avatar.showcase.count', 4)),
            ];
        }

        return $this->settingsCache;
    }

    /**
     * Promozione a tempo attiva adesso (es. saldi di Natale); se ce ne sono
     * piu' di una vale quella con lo sconto maggiore.
     *
     * @return array{name: string, discount: int, scope: string, ends_at: string}|null
     */
    public function promotion(): ?array
    {
        if ($this->promoCache === null) {
            $now = now();
            $promo = DB::table('avatar_promotions')
                ->where('is_active', true)
                ->where('starts_at', '<=', $now)
                ->where('ends_at', '>', $now)
                ->orderByDesc('discount')
                ->first();

            $this->promoCache = $promo ? [
                'name' => $promo->name,
                'discount' => (int) $promo->discount,
                'scope' => $promo->scope,
                'ends_at' => \Carbon\Carbon::parse($promo->ends_at)->toIso8601String(),
            ] : ['none' => true];
        }

        return isset($this->promoCache['none']) ? null : $this->promoCache;
    }

    /**
     * Sconto della vetrina: quello di base, o quello della promozione se maggiore.
     */
    public function showcaseDiscount(): int
    {
        return max($this->settings()['discount'], $this->promotion()['discount'] ?? 0);
    }

    /** @var array<string, array>  cache per richiesta, per settimana */
    private array $showcaseCache = [];

    /**
     * Vetrina della settimana che contiene $date.
     * Se l'admin ha scelto gli oggetti a mano si usano quelli, altrimenti la
     * rotazione e' automatica: ogni settimana l'ordine degli oggetti del
     * negozio si rimescola (sempre uguale per tutti nella stessa settimana).
     *
     * @return array{week_start: string, ends_at: string, discount: int, keys: string[], manual: bool}
     */
    public function showcase(?\Carbon\Carbon $date = null): array
    {
        $weekStart = ($date ?? now())->copy()->startOfWeek();
        $cacheKey = $weekStart->toDateString();

        if (isset($this->showcaseCache[$cacheKey])) {
            return $this->showcaseCache[$cacheKey];
        }

        $manual = AvatarItem::query()
            ->join('avatar_showcase_picks as p', 'p.avatar_item_id', '=', 'avatar_items.id')
            ->where('p.week_start', $cacheKey)
            ->where('avatar_items.is_active', true)
            ->where('avatar_items.is_exclusive', false)
            ->whereNotNull('avatar_items.price')
            ->orderBy('avatar_items.name')
            ->pluck('avatar_items.key')
            ->all();

        $isManual = $manual !== [];
        $keys = $manual;

        if (! $isManual) {
            $keys = $this->rotationKeys($weekStart);
        }

        return $this->showcaseCache[$cacheKey] = [
            'week_start' => $cacheKey,
            'ends_at' => $weekStart->copy()->addWeek()->toIso8601String(),
            'discount' => $this->showcaseDiscount(),
            'keys' => $keys,
            'manual' => $isManual,
        ];
    }

    /**
     * Rotazione automatica: gli oggetti a pagamento escono a gruppi, uno dopo
     * l'altro, finche' non sono usciti tutti (nessuna ripetizione nel giro);
     * poi si rimescola e il giro riparte.
     *
     * @return string[]
     */
    private function rotationKeys(\Carbon\Carbon $weekStart): array
    {
        $all = AvatarItem::where('is_active', true)
            ->where('is_exclusive', false)
            ->whereNotNull('price')
            ->orderBy('id')
            ->pluck('key')
            ->all();

        $count = max(1, $this->settings()['count']);
        $total = count($all);

        if ($total <= $count) {
            return $all;
        }

        $perCycle = (int) ceil($total / $count);
        $week = intdiv($weekStart->copy()->startOfDay()->diffInDays(\Carbon\Carbon::parse('2024-01-01')->startOfDay(), true), 7);
        $cycle = intdiv($week, $perCycle);
        $slot = $week % $perCycle;

        $order = collect($all)->sortBy(fn ($key) => crc32($cycle.'|'.$key))->values()->all();

        $keys = [];
        for ($i = 0; $i < $count; $i++) {
            $keys[] = $order[($slot * $count + $i) % $total];
        }

        return $keys;
    }

    /**
     * Prezzo di listino (senza sconto): quello dell'oggetto, oppure quello
     * proposto per la sua rarita'. Null se l'oggetto non e' acquistabile.
     */
    public function listPrice(AvatarItem $item): ?int
    {
        if ($item->is_exclusive) {
            if (! $item->exclusiveOnSale()) {
                return null;
            }

            return $item->price ?? (int) config('avatar.default_prices.'.$item->rarity, 150);
        }

        return $item->price;
    }

    /**
     * Prezzo che l'utente paga adesso (con lo sconto della vetrina, se in vetrina).
     */
    public function priceFor(AvatarItem $item): ?int
    {
        $price = $this->listPrice($item);

        if ($price === null || $item->is_exclusive) {
            return $price;
        }

        $promo = $this->promotion();

        // In vetrina: sconto di base o della promozione (il maggiore).
        // Fuori vetrina: sconto solo se la promozione vale per tutto il negozio.
        if (in_array($item->key, $this->showcase()['keys'], true)) {
            $discount = $this->showcaseDiscount();
        } elseif ($promo && $promo['scope'] === 'all') {
            $discount = $promo['discount'];
        } else {
            return $price;
        }

        return (int) round($price * (100 - $discount) / 100);
    }

    /**
     * Catalogo visibile all'utente: gli esclusivi non in vendita restano
     * nascosti, tranne a chi li possiede gia'.
     */
    public function catalog(?User $user = null): Collection
    {
        $ownedIds = $user
            ? UserAvatarItem::where('user_id', $user->id)->pluck('avatar_item_id')->flip()
            : collect();
        $showcase = $this->showcase()['keys'];

        return AvatarItem::where('is_active', true)
            ->orderByRaw('unlock_level IS NULL')
            ->orderBy('unlock_level')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (AvatarItem $item) => ! $item->is_exclusive || $item->exclusiveOnSale() || isset($ownedIds[$item->id]))
            ->map(function (AvatarItem $item) use ($showcase) {
                $row = $item->toCatalog();
                $list = $this->listPrice($item);
                $price = $this->priceFor($item);

                $row['price'] = $price;
                $row['original_price'] = $price !== null && $price !== $list ? $list : null;
                $row['discount'] = $price !== null && $list ? (int) round((1 - $price / $list) * 100) : 0;
                $row['showcase'] = in_array($item->key, $showcase, true) && ! $item->is_exclusive;
                $row['exclusive'] = (bool) $item->is_exclusive;
                $row['until'] = $item->is_exclusive && $item->exclusive_until ? $item->exclusive_until->toIso8601String() : null;

                return $row;
            })
            ->values();
    }
}