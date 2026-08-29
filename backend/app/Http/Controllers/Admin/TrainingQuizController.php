<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\TrainingAttempt;
use App\Models\TrainingCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrainingQuizController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $quizzes = Quiz::where('type', 'training')
            ->with('trainingCategory')
            ->withCount('questions')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('trainingCategory', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = TrainingCategory::orderBy('name')->get();

        return view('admin.training.quizzes.index', compact('quizzes', 'categories', 'search'));
    }

    public function create()
    {
        $categories = TrainingCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.training.quizzes.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateTrainingQuiz($request);

        $data = [
            ...$validated,
            'type' => 'training',
            'created_by' => Auth::id(),
            'leaderboard_visible' => true,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('covers', 'public');
        }

        Quiz::create($data);

        return redirect()
            ->route('admin.training.quizzes.index')
            ->with('success', 'Training quiz creato.');
    }

    public function edit(Quiz $quiz)
    {
        $this->ensureTraining($quiz);

        $categories = TrainingCategory::orderBy('name')->get();

        return view('admin.training.quizzes.edit', compact('quiz', 'categories'));
    }

    public function show(Quiz $quiz)
    {
        $this->ensureTraining($quiz);

        return redirect()->route('admin.training.quizzes.edit', $quiz);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->ensureTraining($quiz);

        $data = $this->validateTrainingQuiz($request);

        if ($request->has('remove_image') && $quiz->image_path) {
            Storage::disk('public')->delete($quiz->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($quiz->image_path) {
                Storage::disk('public')->delete($quiz->image_path);
            }

            $data['image_path'] = $request->file('image')->store('covers', 'public');
        }

        $quiz->update($data);

        return redirect()
            ->route('admin.training.quizzes.index')
            ->with('success', 'Training quiz aggiornato.');
    }

    public function destroy(Quiz $quiz)
    {
        $this->ensureTraining($quiz);

        if ($quiz->image_path) {
            Storage::disk('public')->delete($quiz->image_path);
        }

        $quiz->delete();

        return redirect()
            ->route('admin.training.quizzes.index')
            ->with('success', 'Training quiz eliminato.');
    }

    public function leaderboard(Quiz $quiz)
    {
        $this->ensureTraining($quiz);

        $attempts = TrainingAttempt::with('user')
            ->where('quiz_id', $quiz->id)
            ->get()
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

        $results = $attempts->map(function ($attempt) {
            $attempt->correct_answers = $attempt->correct_answers ?? 0;
            $attempt->total_questions = $attempt->total_questions ?? 0;

            return $attempt;
        });

        return view('admin.training.quizzes.leaderboard', compact('quiz', 'results'));
    }

    public function toggleLeaderboard(Quiz $quiz)
    {
        $this->ensureTraining($quiz);

        $quiz->update([
            'leaderboard_visible' => ! $quiz->leaderboard_visible,
        ]);

        return back()->with('success', 'Visibilità classifica aggiornata con successo.');
    }

    private function validateTrainingQuiz(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'training_category_id' => ['required', 'exists:training_categories,id'],
            'training_question_mode' => ['required', 'in:5,10,all'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);
    }

    private function ensureTraining(Quiz $quiz): void
    {
        abort_unless($quiz->type === 'training', 404);
    }
}
