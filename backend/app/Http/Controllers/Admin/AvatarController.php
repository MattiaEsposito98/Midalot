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
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] ??= $item->sort_order;

        // I vestiti base (livello 1) devono restare sempre disponibili.
        if ($item->unlock_level === 1 && (! $data['is_active'] || (int) ($data['unlock_level'] ?? 0) !== 1)) {
            return back()->withInput()->withErrors(['unlock_level' => 'I vestiti base (livello 1) non si possono disattivare né spostare di livello.']);
        }

        $item->update($data);

        return redirect()->route('admin.avatar.index')->with('success', "Oggetto aggiornato: {$item->name}");
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
