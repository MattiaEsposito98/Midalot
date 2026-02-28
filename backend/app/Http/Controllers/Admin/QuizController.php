<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $quizzes = Quiz::latest()->get();

        return view('admin.quizzes.index', compact('quizzes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.quizzes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Quiz::create([
            'title' => $request->title,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'is_active' => false,
        ]);

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz creato con successo!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $quiz = Quiz::findOrFail($id);

        return view('admin.quizzes.edit', compact('quiz'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $quiz = Quiz::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $quiz->update([
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $request->is_active,
        ]);

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz aggiornato con successo!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $quiz = Quiz::findOrFail($id);

        $quiz->delete();

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz eliminato con successo!');
    }

    public function searchUsers(Request $request, Quiz $quiz)
    {
        $query = $request->get('q');

        return \App\Models\User::where(function ($q) use ($query) {
            $q->where('nickname', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
        })
            ->whereNotIn('id', $quiz->users()->pluck('users.id'))
            ->select('id', 'nickname', 'email')
            ->limit(20)
            ->get();
    }

    public function attachUsers(Request $request, \App\Models\Quiz $quiz)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id'
        ]);

        $quiz->users()->syncWithoutDetaching($request->users);

        return back()->with('success', 'Utenti associati correttamente.');
    }

    public function manageUsers(Quiz $quiz)
    {
        $attachedUsers = $quiz->users()
            ->select('users.id', 'nickname', 'email')
            ->get();

        return view('admin.quizzes.users', compact('quiz', 'attachedUsers'));
    }

    public function detachUser(Quiz $quiz, \App\Models\User $user)
    {
        $quiz->users()->detach($user->id);

        return back()->with('success', 'Utente rimosso dal quiz.');
    }
}
