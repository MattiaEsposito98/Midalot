<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvatarItem;
use App\Models\CoinEvent;
use App\Models\User;
use App\Models\UserAvatar;
use App\Models\UserAvatarItem;
use App\Services\AvatarService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Avatar e negozio: oggetti (prezzi, livelli, attivi), regali agli utenti e
 * statistiche su monete e acquisti.
 */
class AvatarController extends Controller
{
    public const SLOT_LABELS = [
        'head' => 'Testa',
        'face' => 'Occhiali',
        'torso' => 'Maglia',
        'armor' => 'Armatura',
        'neck' => 'Collo',
        'hands' => 'Guanti',
        'legs' => 'Pantaloni',
        'feet' => 'Scarpe',
        'handR' => 'Mano destra',
        'handL' => 'Mano sinistra',
        'back' => 'Schiena',
        'pet' => 'Compagno',
        'aura' => 'Aura',
        'background' => 'Sfondo',
        'title' => 'Titolo',
        'frame' => 'Cornice',
    ];

    public const RARITIES = ['base', 'comune', 'raro', 'epico', 'leggendario'];

    public function index(Request $request)
    {
        $slot = $request->get('slot', 'all');
        $source = $request->get('source', 'all');
        $state = $request->get('state', 'all');
        $search = trim((string) $request->get('search', ''));

        $owners = UserAvatarItem::select('avatar_item_id', DB::raw('COUNT(*) as total'))->groupBy('avatar_item_id')->pluck('total', 'avatar_item_id');
        $purchases = UserAvatarItem::where('source', 'shop')->select('avatar_item_id', DB::raw('COUNT(*) as total'))->groupBy('avatar_item_id')->pluck('total', 'avatar_item_id');

        $items = AvatarItem::query()
            ->when(isset(self::SLOT_LABELS[$slot]), fn ($q) => $q->where('slot', $slot))
            ->when($source === 'level', fn ($q) => $q->whereNotNull('unlock_level'))
            ->when($source === 'shop', fn ($q) => $q->whereNotNull('price'))
            ->when($state === 'active', fn ($q) => $q->where('is_active', true))
            ->when($state === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('key', 'like', $like));
            })
            ->orderByRaw('unlock_level IS NULL')
            ->orderBy('unlock_level')
            ->orderBy('sort_order')
            ->get();

        return view('admin.avatar.index', [
            'items' => $items,
            'owners' => $owners,
            'purchases' => $purchases,
            'slot' => $slot,
            'source' => $source,
            'state' => $state,
            'search' => $search,
            'stats' => $this->stats(),
            'slotLabels' => self::SLOT_LABELS,
            'allItems' => AvatarItem::orderBy('name')->get(['key', 'name', 'slot']),
        ]);
    }

    public function edit(AvatarItem $item)
    {
        return view('admin.avatar.edit', [
            'item' => $item,
            'slotLabels' => self::SLOT_LABELS,
            'rarities' => self::RARITIES,
            'defaultPrices' => config('avatar.default_prices'),
            'showcase' => app(AvatarService::class)->showcase(),
            'owners' => UserAvatarItem::where('avatar_item_id', $item->id)->count(),
            'purchases' => UserAvatarItem::where('avatar_item_id', $item->id)->where('source', 'shop')->count(),
        ]);
    }

    public function update(Request $request, AvatarItem $item)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'rarity' => ['required', Rule::in(self::RARITIES)],
            'unlock_level' => ['nullable', 'integer', 'min:1', 'max:500'],
            'price' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'shop_min_level' => ['required', 'integer', 'min:1', 'max:500'],
            'exclusive_until' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_exclusive'] = $request->boolean('is_exclusive');
        $data['exclusive_open'] = $data['is_exclusive'] && $request->boolean('exclusive_open');

        // La scadenza vale solo per gli esclusivi aperti.
        if (! $data['exclusive_open']) {
            $data['exclusive_until'] = null;
        }

        if ($data['exclusive_open'] && ! empty($data['exclusive_until']) && \Illuminate\Support\Carbon::parse($data['exclusive_until'])->isPast()) {
            return back()->withInput()->withErrors(['exclusive_until' => 'La scadenza è già passata.']);
        }

        // Un esclusivo senza prezzo usa quello proposto per la sua rarità.
        if ($data['is_exclusive'] && ($data['unlock_level'] ?? null) === null && $data['price'] === null) {
            $data['price'] = (int) config('avatar.default_prices.'.$data['rarity'], 150);
        }
        $data['sort_order'] ??= $item->sort_order;

        // I vestiti base (livello 1) devono restare sempre disponibili.
        if ($item->unlock_level === 1 && (! $data['is_active'] || (int) ($data['unlock_level'] ?? 0) !== 1)) {
            return back()->withInput()->withErrors(['unlock_level' => 'I vestiti base (livello 1) non si possono disattivare né spostare di livello.']);
        }

        $item->update($data);

        return redirect()->route('admin.avatar.index')->with('success', "Oggetto aggiornato: {$item->name}");
    }

    /**
     * Vetrina settimanale: questa settimana e la prossima (automatica o scelta a mano).
     */
    public function showcase(AvatarService $avatar)
    {
        $weeks = [];
        foreach (['this' => now(), 'next' => now()->addWeek()] as $name => $date) {
            $show = $avatar->showcase($date);
            $weeks[$name] = $show + [
                'items' => AvatarItem::whereIn('key', $show['keys'])->get(),
                'start' => $date->copy()->startOfWeek(),
            ];
        }

        return view('admin.avatar.showcase', [
            'weeks' => $weeks,
            'shopItems' => AvatarItem::where('is_active', true)->where('is_exclusive', false)->whereNotNull('price')->orderBy('name')->get(['key', 'name', 'slot', 'price']),
            'slotLabels' => self::SLOT_LABELS,
            'count' => $avatar->settings()['count'],
            'discount' => $avatar->settings()['discount'],
            'defaults' => config('avatar.showcase'),
            'activePromo' => $avatar->promotion(),
            // Temi con oggetti esclusivi (stagionali): si aprono/chiudono tutti insieme.
            'seasonal' => collect(config('avatar.themes', []))->map(function ($def, $key) {
                $items = AvatarItem::where('theme', $key)->where('is_exclusive', true)->get();

                return $items->isEmpty() ? null : [
                    'key' => $key,
                    'name' => $def['name'],
                    'count' => $items->count(),
                    'open' => $items->filter->exclusiveOnSale()->count(),
                    'until' => $items->max('exclusive_until'),
                ];
            })->filter()->values(),
            'promotions' => DB::table('avatar_promotions')->orderByDesc('starts_at')->get()->map(function ($p) {
                $p->starts_at = \Illuminate\Support\Carbon::parse($p->starts_at);
                $p->ends_at = \Illuminate\Support\Carbon::parse($p->ends_at);

                return $p;
            }),
        ]);
    }

    /**
     * Impostazioni di base della vetrina (sconto e numero di oggetti).
     */
    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'discount' => ['required', 'integer', 'min:1', 'max:90'],
            'count' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        foreach (['showcase_discount' => $data['discount'], 'showcase_count' => $data['count']] as $key => $value) {
            DB::table('avatar_settings')->updateOrInsert(['key' => $key], ['value' => (string) $value, 'updated_at' => now(), 'created_at' => now()]);
        }

        return redirect()->route('admin.avatar.showcase')->with('success', 'Impostazioni della vetrina salvate.');
    }

    /**
     * Apre o chiude tutti gli oggetti esclusivi di un tema stagionale.
     */
    public function themeSale(Request $request, string $theme)
    {
        abort_unless(isset(config('avatar.themes')[$theme]), 404);

        $data = $request->validate([
            'action' => ['required', Rule::in(['open', 'close'])],
            'until' => ['nullable', 'date', 'after:now'],
        ]);

        $open = $data['action'] === 'open';
        AvatarItem::where('theme', $theme)->where('is_exclusive', true)->update([
            'exclusive_open' => $open,
            'exclusive_until' => $open ? ($data['until'] ?? null) : null,
        ]);

        $name = config('avatar.themes')[$theme]['name'];

        return redirect()->route('admin.avatar.showcase')->with('success', $open ? "Tema {$name} in vendita." : "Tema {$name} chiuso.");
    }

    /**
     * Promozione a tempo (es. saldi di Natale).
     */
    public function storePromotion(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'discount' => ['required', 'integer', 'min:1', 'max:90'],
            'scope' => ['required', Rule::in(['showcase', 'all'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        DB::table('avatar_promotions')->insert($data + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('admin.avatar.showcase')->with('success', "Promozione creata: {$data['name']}.");
    }

    public function togglePromotion(int $promotion)
    {
        $row = DB::table('avatar_promotions')->where('id', $promotion)->first();
        abort_unless($row, 404);
        DB::table('avatar_promotions')->where('id', $promotion)->update(['is_active' => ! $row->is_active, 'updated_at' => now()]);

        return redirect()->route('admin.avatar.showcase')->with('success', $row->is_active ? 'Promozione sospesa.' : 'Promozione riattivata.');
    }

    public function deletePromotion(int $promotion)
    {
        DB::table('avatar_promotions')->where('id', $promotion)->delete();

        return redirect()->route('admin.avatar.showcase')->with('success', 'Promozione eliminata.');
    }

    /**
     * Sceglie a mano gli oggetti di una settimana (o torna alla rotazione automatica).
     */
    public function saveShowcase(Request $request)
    {
        $data = $request->validate([
            'week' => ['required', Rule::in(['this', 'next'])],
            'items' => ['nullable', 'array', 'max:12'],
            'items.*' => ['string', 'exists:avatar_items,key'],
        ]);

        $weekStart = ($data['week'] === 'next' ? now()->addWeek() : now())->startOfWeek()->toDateString();
        $ids = AvatarItem::whereIn('key', $data['items'] ?? [])
            ->where('is_exclusive', false)
            ->whereNotNull('price')
            ->pluck('id');

        DB::transaction(function () use ($weekStart, $ids) {
            DB::table('avatar_showcase_picks')->where('week_start', $weekStart)->delete();
            DB::table('avatar_showcase_picks')->insert($ids->map(fn ($id) => [
                'week_start' => $weekStart,
                'avatar_item_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        });

        return redirect()->route('admin.avatar.showcase')->with('success', $ids->isEmpty()
            ? 'Vetrina tornata alla rotazione automatica.'
            : 'Vetrina salvata: '.$ids->count().' oggetti scelti a mano.');
    }

    /**
     * Regalo di un oggetto e/o di monete a un utente (per nickname o email).
     */
    public function gift(Request $request, AvatarService $avatar)
    {
        $data = $request->validate([
            'user' => ['required', 'string', 'max:255'],
            'item' => ['nullable', 'string', 'exists:avatar_items,key'],
            'coins' => ['nullable', 'integer', 'min:-100000', 'max:100000'],
        ]);

        $user = User::where('nickname', $data['user'])->orWhere('email', $data['user'])->first();
        if (! $user) {
            return back()->withInput()->withErrors(['user' => 'Nessun utente con questo nickname o email.']);
        }

        if (empty($data['item']) && empty($data['coins'])) {
            return back()->withInput()->withErrors(['item' => 'Scegli un oggetto o un numero di monete.']);
        }

        $done = [];

        if (! empty($data['item'])) {
            $item = AvatarItem::where('key', $data['item'])->first();
            $created = UserAvatarItem::firstOrCreate(
                ['user_id' => $user->id, 'avatar_item_id' => $item->id],
                // seen_at vuoto: l'utente vedra' l'avviso "nuovo oggetto".
                ['source' => 'admin', 'seen_at' => null]
            );
            $done[] = $created->wasRecentlyCreated ? "oggetto {$item->name}" : "{$item->name} (lo aveva gia')";
        }

        if (! empty($data['coins'])) {
            $coins = (int) $data['coins'];
            if ($coins < 0 && $user->coins + $coins < 0) {
                $coins = -$user->coins;
            }
            // source_id unico: ogni regalo e' un movimento a se'.
            $avatar->addCoins($user->id, 'admin', (int) (microtime(true) * 1000), $coins);
            $done[] = ($coins >= 0 ? '+' : '').$coins.' monete';
        }

        return redirect()->route('admin.avatar.user', $user)->with('success', "A {$user->nickname}: ".implode(', ', $done));
    }

    /**
     * Inventario, monete e avatar di un utente.
     */
    public function user(User $user, AvatarService $avatar)
    {
        $owned = UserAvatarItem::with('item')->where('user_id', $user->id)->get()
            ->sortBy(fn ($row) => [$row->item?->unlock_level ?? 999, $row->item?->name]);

        return view('admin.avatar.user', [
            'user' => $user,
            'level' => XpService::info((int) $user->xp_total)['level'],
            'owned' => $owned,
            'config' => UserAvatar::where('user_id', $user->id)->exists() ? $avatar->config($user) : null,
            'coinEvents' => CoinEvent::where('user_id', $user->id)->latest('id')->limit(30)->get(),
            'slotLabels' => self::SLOT_LABELS,
            'allItems' => AvatarItem::orderBy('name')->get(['key', 'name', 'slot']),
        ]);
    }

    /**
     * Toglie un oggetto a un utente (se lo indossa, torna al vestito base o a niente).
     * Se era stato comprato, le monete NON vengono restituite in automatico.
     */
    public function revoke(User $user, AvatarItem $item)
    {
        if ($item->unlock_level === 1) {
            return back()->withErrors(['item' => 'I vestiti base non si possono togliere.']);
        }

        UserAvatarItem::where('user_id', $user->id)->where('avatar_item_id', $item->id)->delete();

        if ($user->avatar_frame === $item->key) {
            $user->forceFill(['avatar_frame' => null])->save();
        }
        if ($user->avatar_title === $item->key) {
            $user->forceFill(['avatar_title' => null])->save();
        }

        return back()->with('success', "Tolto {$item->name} a {$user->nickname}.");
    }

    private function stats(): array
    {
        $topItems = DB::table('user_avatar_items as u')
            ->join('avatar_items as i', 'i.id', '=', 'u.avatar_item_id')
            ->where('u.source', 'shop')
            ->select('i.name', DB::raw('COUNT(*) as total'))
            ->groupBy('i.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'items_total' => AvatarItem::count(),
            'items_active' => AvatarItem::where('is_active', true)->count(),
            'items_shop' => AvatarItem::whereNotNull('price')->where('is_active', true)->count(),
            'avatars_saved' => UserAvatar::count(),
            'portraits' => User::whereNotNull('avatar_url')->count(),
            'coins_circulating' => (int) User::sum('coins'),
            'coins_earned' => (int) CoinEvent::where('amount', '>', 0)->where('source', '!=', 'admin')->sum('amount'),
            'coins_spent' => (int) -CoinEvent::where('source', 'shop')->sum('amount'),
            'purchases' => UserAvatarItem::where('source', 'shop')->count(),
            'buyers' => UserAvatarItem::where('source', 'shop')->distinct('user_id')->count('user_id'),
            'gifts' => UserAvatarItem::where('source', 'admin')->count(),
            'top_items' => $topItems,
            'richest' => User::where('coins', '>', 0)->orderByDesc('coins')->limit(5)->get(['id', 'nickname', 'coins']),
        ];
    }
}
