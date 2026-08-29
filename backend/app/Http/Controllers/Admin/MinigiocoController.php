<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MinigiocoController extends Controller
{
    public function index()
    {
        $minigiochi = Minigioco::withCount(['rounds', 'attempts'])
            ->latest()
            ->get();

        return view('admin.minigiochi.index', compact('minigiochi'));
    }

    public function create()
    {
        return view('admin.minigiochi.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Minigioco::create([
            'title' => $request->title,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'is_active' => false,
            'leaderboard_visible' => true,
        ]);

        return redirect()
            ->route('admin.minigiochi.index')
            ->with('success', 'Minigioco creato con successo!');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $minigioco = Minigioco::findOrFail($id);

        return view('admin.minigiochi.edit', compact('minigioco'));
    }

    public function update(Request $request, string $id)
    {
        $minigioco = Minigioco::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $minigioco->update([
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $request->is_active,
        ]);

        return redirect()
            ->route('admin.minigiochi.index')
            ->with('success', 'Minigioco aggiornato con successo!');
    }

    public function destroy(string $id)
    {
        $minigioco = Minigioco::findOrFail($id);

        $minigioco->delete();

        return redirect()
            ->route('admin.minigiochi.index')
            ->with('success', 'Minigioco eliminato con successo!');
    }

    public function leaderboard(Minigioco $minigioco)
    {
        $attempts = MinigiocoAttempt::with(['user', 'risposte'])
            ->where('minigioco_id', $minigioco->id)
            ->get();

        $results = $attempts->map(function ($attempt) use ($minigioco) {

            $correct = $attempt->risposte?->where('is_correct', true)->count() ?? 0;
            $total = $minigioco->rounds()->count();

            $attempt->correct_answers = $correct;
            $attempt->total_questions = $total;

            return $attempt;
        })
            ->sort(function ($a, $b) {

                if ($a->completed !== $b->completed) {
                    return $a->completed ? -1 : 1;
                }

                if ($a->completed && $b->completed) {

                    if ($a->score !== $b->score) {
                        return $b->score <=> $a->score;
                    }

                    return ($a->total_time ?? PHP_INT_MAX) <=> ($b->total_time ?? PHP_INT_MAX);
                }

                return 0;
            })
            ->values();

        return view('admin.minigiochi.leaderboard', compact('minigioco', 'results'));
    }

    public function toggleLeaderboard(Minigioco $minigioco)
    {
        $minigioco->update([
            'leaderboard_visible' => ! $minigioco->leaderboard_visible,
        ]);

        return back()->with('success', 'Visibilità classifica aggiornata con successo.');
    }
}
