<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'is_active' => false,
            'leaderboard_visible' => true,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('covers', 'public');
        }

        Minigioco::create($data);

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
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $request->is_active,
        ];

        if ($request->has('remove_image') && $minigioco->image_path) {
            Storage::disk('public')->delete($minigioco->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($minigioco->image_path) {
                Storage::disk('public')->delete($minigioco->image_path);
            }

            $data['image_path'] = $request->file('image')->store('covers', 'public');
        }

        $minigioco->update($data);

        return redirect()
            ->route('admin.minigiochi.index')
            ->with('success', 'Minigioco aggiornato con successo!');
    }

    public function destroy(string $id)
    {
        $minigioco = Minigioco::findOrFail($id);

        if ($minigioco->image_path) {
            Storage::disk('public')->delete($minigioco->image_path);
        }

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
