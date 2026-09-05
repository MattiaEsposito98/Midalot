<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Minigioco;
use App\Models\MinigiocoRound;
use App\Models\MinigiocoRoundItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinigiocoRoundController extends Controller
{
    public function index(string $minigioco)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        $rounds = $minigioco->rounds()
            ->with('items')
            ->orderBy('id')
            ->get();

        return view('admin.minigioco_rounds.index', compact('minigioco', 'rounds'));
    }

    public function create(string $minigioco)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        return view("admin.minigioco_rounds.create_{$this->viewSuffix($minigioco)}", compact('minigioco'));
    }

    public function store(Request $request, string $minigioco)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        return match ($minigioco->tipo) {
            'salto_temporale' => $this->storeItemsRound($request, $minigioco),
            'trova_intruso' => $this->storeItemsRound($request, $minigioco, intruso: true),
            default => $this->storeTastieraRotta($request, $minigioco),
        };
    }

    public function show(string $minigioco, string $round)
    {
        //
    }

    public function edit(string $minigioco, string $round)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        $round = MinigiocoRound::where('minigioco_id', $minigioco->id)
            ->with('items')
            ->findOrFail($round);

        return view("admin.minigioco_rounds.edit_{$this->viewSuffix($minigioco)}", compact('minigioco', 'round'));
    }

    public function update(Request $request, string $minigioco, string $round)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        $round = MinigiocoRound::where('minigioco_id', $minigioco->id)
            ->findOrFail($round);

        return match ($minigioco->tipo) {
            'salto_temporale' => $this->updateItemsRound($request, $minigioco, $round),
            'trova_intruso' => $this->updateItemsRound($request, $minigioco, $round, intruso: true),
            default => $this->updateTastieraRotta($request, $minigioco, $round),
        };
    }

    public function destroy(string $minigioco, string $round)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        $round = MinigiocoRound::where('minigioco_id', $minigioco->id)
            ->with('items')
            ->findOrFail($round);

        foreach ($round->items as $item) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
        }

        $round->delete();

        return back()->with('success', 'Domanda eliminata!');
    }

    private function viewSuffix(Minigioco $minigioco): string
    {
        return match ($minigioco->tipo) {
            'salto_temporale' => 'salto_temporale',
            'trova_intruso' => 'trova_intruso',
            default => 'tastiera_rotta',
        };
    }

    private function storeTastieraRotta(Request $request, Minigioco $minigioco)
    {
        $request->validate([
            'parola' => 'required|string|max:100',
            'direzione' => 'required|in:sinistra,destra',
            'quantita' => 'required|integer|min:1|max:9',
            'time_limit_seconds' => 'required|integer|min:5',
        ]);

        MinigiocoRound::create([
            'minigioco_id' => $minigioco->id,
            'parola_originale' => Str::upper($request->parola),
            'shift' => $this->resolveShift($request->direzione, $request->quantita),
            'time_limit_seconds' => $request->time_limit_seconds,
        ]);

        return redirect()
            ->route('admin.minigiochi.rounds.index', $minigioco->id)
            ->with('success', 'Domanda creata con successo!');
    }

    private function updateTastieraRotta(Request $request, Minigioco $minigioco, MinigiocoRound $round)
    {
        $request->validate([
            'parola' => 'required|string|max:100',
            'direzione' => 'required|in:sinistra,destra',
            'quantita' => 'required|integer|min:1|max:9',
            'time_limit_seconds' => 'required|integer|min:5',
        ]);

        $round->update([
            'parola_originale' => Str::upper($request->parola),
            'shift' => $this->resolveShift($request->direzione, $request->quantita),
            'time_limit_seconds' => $request->time_limit_seconds,
        ]);

        return redirect()
            ->route('admin.minigiochi.rounds.index', $minigioco->id)
            ->with('success', 'Domanda aggiornata!');
    }

    /**
     * Salto Temporale e Trova l'Intruso condividono lo stesso form: un round
     * è un puzzle con esattamente 4 elementi (label + immagine opzionale),
     * creati/aggiornati tutti insieme in un'unica transazione.
     */
    private function storeItemsRound(Request $request, Minigioco $minigioco, bool $intruso = false)
    {
        $contentMode = $request->input('content_mode') === 'immagine' ? 'immagine' : 'testo';

        $rules = [
            'time_limit_seconds' => 'required|integer|min:5',
            'content_mode' => 'required|in:testo,immagine',
            'items' => 'required|array|size:4',
        ];

        if ($contentMode === 'immagine') {
            $rules['items.*.image'] = 'required|image|mimes:jpg,jpeg,png|max:2048';
        } else {
            $rules['items.*.label'] = 'required|string|max:255';
            $rules['items.*.image'] = 'nullable|image|mimes:jpg,jpeg,png|max:2048';
        }

        if ($intruso) {
            $rules['intruso'] = 'required|integer|min:0|max:3';
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $minigioco, $intruso, $contentMode) {
            $round = MinigiocoRound::create([
                'minigioco_id' => $minigioco->id,
                'time_limit_seconds' => $request->time_limit_seconds,
                'content_mode' => $contentMode,
            ]);

            foreach ($request->items as $index => $item) {
                $imagePath = $contentMode === 'immagine' && $request->hasFile("items.{$index}.image")
                    ? $request->file("items.{$index}.image")->store('minigioco-round-items', 'public')
                    : null;

                MinigiocoRoundItem::create([
                    'minigioco_round_id' => $round->id,
                    'ordine' => $index + 1,
                    'label' => $contentMode === 'testo' ? $item['label'] : null,
                    'image_path' => $imagePath,
                    'is_intruso' => $intruso && (int) $request->intruso === (int) $index,
                ]);
            }
        });

        return redirect()
            ->route('admin.minigiochi.rounds.index', $minigioco->id)
            ->with('success', 'Puzzle creato con successo!');
    }

    private function updateItemsRound(Request $request, Minigioco $minigioco, MinigiocoRound $round, bool $intruso = false)
    {
        $contentMode = $request->input('content_mode') === 'immagine' ? 'immagine' : 'testo';

        $rules = [
            'time_limit_seconds' => 'required|integer|min:5',
            'content_mode' => 'required|in:testo,immagine',
            'items' => 'required|array|size:4',
            'items.*.image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'items.*.remove_image' => 'nullable|boolean',
        ];

        if ($contentMode === 'testo') {
            $rules['items.*.label'] = 'required|string|max:255';
        }

        if ($intruso) {
            $rules['intruso'] = 'required|integer|min:0|max:3';
        }

        $request->validate($rules);

        $existingItems = $round->items()->orderBy('ordine')->get()->values();

        if ($contentMode === 'immagine') {
            foreach ($request->items as $index => $item) {
                $existing = $existingItems->get($index);
                $keepsExisting = $existing?->image_path && ! $request->boolean("items.{$index}.remove_image");
                $hasNewUpload = $request->hasFile("items.{$index}.image");

                if (! $keepsExisting && ! $hasNewUpload) {
                    return back()
                        ->withErrors(['items.'.$index.'.image' => 'Elemento '.($index + 1).': carica un\'immagine.'])
                        ->withInput();
                }
            }
        }

        DB::transaction(function () use ($request, $minigioco, $round, $intruso, $contentMode, $existingItems) {
            $round->update([
                'time_limit_seconds' => $request->time_limit_seconds,
                'content_mode' => $contentMode,
            ]);

            foreach ($request->items as $index => $item) {
                $existing = $existingItems->get($index);
                $imagePath = $existing?->image_path;

                if ($request->boolean("items.{$index}.remove_image") && $imagePath) {
                    Storage::disk('public')->delete($imagePath);
                    $imagePath = null;
                }

                if ($request->hasFile("items.{$index}.image")) {
                    if ($imagePath) {
                        Storage::disk('public')->delete($imagePath);
                    }

                    $imagePath = $request->file("items.{$index}.image")->store('minigioco-round-items', 'public');
                }

                if ($contentMode === 'testo' && $imagePath) {
                    Storage::disk('public')->delete($imagePath);
                    $imagePath = null;
                }

                $label = $contentMode === 'testo' ? $item['label'] : null;
                $isIntruso = $intruso && (int) $request->intruso === (int) $index;

                if ($existing) {
                    $existing->update([
                        'label' => $label,
                        'image_path' => $imagePath,
                        'is_intruso' => $isIntruso,
                    ]);
                } else {
                    MinigiocoRoundItem::create([
                        'minigioco_round_id' => $round->id,
                        'ordine' => $index + 1,
                        'label' => $label,
                        'image_path' => $imagePath,
                        'is_intruso' => $isIntruso,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.minigiochi.rounds.index', $minigioco->id)
            ->with('success', 'Puzzle aggiornato!');
    }

    private function resolveShift(string $direzione, int $quantita): int
    {
        return $direzione === 'sinistra' ? -$quantita : $quantita;
    }
}
