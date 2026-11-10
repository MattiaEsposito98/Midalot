<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvatarItem;
use App\Services\AchievementService;
use App\Services\AvatarService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AvatarController extends Controller
{
    public function __construct(private AvatarService $avatar, private AchievementService $achievements)
    {
    }

    /**
     * Avatar dell'utente, oggetti posseduti, catalogo completo e monete.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $this->avatar->syncUnlocks($user);
        $this->achievements->check($user->id);

        return response()->json($this->payload($user));
    }

    public function update(Request $request)
    {
        $appearance = config('avatar.appearance');
        $hex = 'regex:/^#[0-9a-fA-F]{6}$/';

        $data = $request->validate([
            'gender' => ['required', Rule::in($appearance['gender'])],
            'skin' => ['required', $hex],
            'faceShape' => ['required', Rule::in($appearance['faceShape'])],
            'brows' => ['required', Rule::in($appearance['brows'])],
            'mouth' => ['required', Rule::in($appearance['mouth'])],
            'beard' => ['required', Rule::in($appearance['beard'])],
            'freckles' => ['required', 'boolean'],
            'hair' => ['required', Rule::in($appearance['hair'])],
            'hairColor' => ['required', $hex],
            'eyes' => ['required', $hex],
            'emblem' => ['required', 'array'],
            'emblem.bg' => ['required', $hex],
            'emblem.fg' => ['required', $hex],
            'emblem.symbol' => ['required', Rule::in(config('avatar.emblem_symbols'))],
            'equipped' => ['required', 'array'],
            'equipped.*' => ['nullable', 'string', 'max:60'],
            'itemColors' => ['nullable', 'array'],
            'itemColors.*' => ['nullable', 'string', 'max:30'],
        ]);

        $unknown = array_diff(array_keys($data['equipped']), AvatarItem::SLOTS);
        if ($unknown) {
            return response()->json(['message' => 'Zona non valida: '.implode(', ', $unknown)], 422);
        }

        $data['emblem'] = [
            'bg' => $data['emblem']['bg'],
            'fg' => $data['emblem']['fg'],
            'symbol' => $data['emblem']['symbol'],
        ];

        $this->avatar->save($request->user(), $data);

        return response()->json($this->payload($request->user()));
    }

    public function buy(Request $request, string $key)
    {
        $item = AvatarItem::where('key', $key)->where('is_active', true)->firstOrFail();

        $this->avatar->buy($request->user(), $item);
        $this->achievements->check($request->user()->id);

        return response()->json([
            'message' => "Hai comprato: {$item->name}",
            ...$this->payload($request->user()->fresh()),
        ]);
    }

    /**
     * Ritratto dell'avatar (PNG/WebP quadrato generato dal browser al salvataggio).
     * Il nome del file cambia a ogni caricamento, cosi' nessuna cache mostra quello vecchio.
     */
    public function portrait(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:png,webp', 'max:400', 'dimensions:min_width=64,max_width=512,ratio=1'],
        ]);

        $user = $request->user();
        $disk = Storage::disk('public');
        $old = $user->avatar_url;

        $ext = $request->file('image')->extension() === 'webp' ? 'webp' : 'png';
        $path = $request->file('image')->storeAs('avatars', $user->id.'-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$ext, 'public');

        $user->forceFill(['avatar_url' => $disk->url($path)])->save();

        // Cancella il ritratto precedente.
        if ($old && str_contains($old, '/storage/avatars/')) {
            $disk->delete('avatars/'.basename(parse_url($old, PHP_URL_PATH)));
        }

        return response()->json(['avatar_url' => $user->avatar_url]);
    }

    /**
     * Nuovi oggetti sbloccati da mostrare nell'avviso (sincronizza prima i livelli).
     */
    public function news(Request $request)
    {
        $user = $request->user();
        $this->avatar->syncUnlocks($user);
        $this->achievements->check($user->id);

        return response()->json([
            'achievements' => $this->achievements->unseen($user),
            'items' => $this->avatar->unseen($user)->map->toCatalog()->values(),
            'level' => XpService::info((int) $user->xp_total)['level'],
        ]);
    }

    public function seen(Request $request)
    {
        $this->avatar->markSeen($request->user());
        $this->achievements->markSeen($request->user());

        return response()->json(['ok' => true]);
    }

    /**
     * Obiettivi con progresso e premi.
     */
    public function achievements(Request $request)
    {
        $user = $request->user();
        $this->achievements->check($user->id);

        return response()->json([
            'achievements' => $this->achievements->listFor($user),
            'coins' => (int) $user->fresh()->coins,
        ]);
    }

    private function payload($user): array
    {
        $owned = $this->avatar->ownedKeys($user);

        return [
            'config' => $this->avatar->config($user, $owned),
            'owned' => $owned,
            'catalog' => $this->avatar->catalog(),
            'coins' => (int) $user->fresh()->coins,
            'level' => XpService::info((int) $user->xp_total)['level'],
            'unseen' => $this->avatar->unseen($user)->pluck('key'),
            'achievement_items' => AchievementService::itemSources(),
            // Colori degli oggetti con tavolozza e quali sono sbloccati per questo utente.
            'palettes' => $this->avatar->palettesFor($user),
        ];
    }
}
