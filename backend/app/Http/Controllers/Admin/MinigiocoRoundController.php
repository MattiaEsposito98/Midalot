<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Minigioco;
use App\Models\MinigiocoRound;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MinigiocoRoundController extends Controller
{
    public function index(string $minigioco)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        $rounds = $minigioco->rounds()
            ->orderBy('id')
            ->get();

        return view('admin.minigioco_rounds.index', compact('minigioco', 'rounds'));
    }

    public function create(string $minigioco)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        return view('admin.minigioco_rounds.create', compact('minigioco'));
    }

    public function store(Request $request, string $minigioco)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

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

    public function show(string $minigioco, string $round)
    {
        //
    }

    public function edit(string $minigioco, string $round)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        $round = MinigiocoRound::where('minigioco_id', $minigioco->id)
            ->findOrFail($round);

        return view('admin.minigioco_rounds.edit', compact('minigioco', 'round'));
    }

    public function update(Request $request, string $minigioco, string $round)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        $round = MinigiocoRound::where('minigioco_id', $minigioco->id)
            ->findOrFail($round);

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

    public function destroy(string $minigioco, string $round)
    {
        $minigioco = Minigioco::findOrFail($minigioco);

        $round = MinigiocoRound::where('minigioco_id', $minigioco->id)
            ->findOrFail($round);

        $round->delete();

        return back()->with('success', 'Domanda eliminata!');
    }

    private function resolveShift(string $direzione, int $quantita): int
    {
        return $direzione === 'sinistra' ? -$quantita : $quantita;
    }
}
