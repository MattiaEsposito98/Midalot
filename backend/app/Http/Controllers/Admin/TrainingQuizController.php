<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\TrainingCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->paginate(20)
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

        Quiz::create([
            ...$validated,
            'type' => 'training',
            'created_by' => Auth::id(),
            'leaderboard_visible' => true,
        ]);

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

        $quiz->update($this->validateTrainingQuiz($request));

        return redirect()
            ->route('admin.training.quizzes.index')
            ->with('success', 'Training quiz aggiornato.');
    }

    public function destroy(Quiz $quiz)
    {
        $this->ensureTraining($quiz);

        $quiz->delete();

        return redirect()
            ->route('admin.training.quizzes.index')
            ->with('success', 'Training quiz eliminato.');
    }

    private function validateTrainingQuiz(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'training_category_id' => ['required', 'exists:training_categories,id'],
            'training_question_mode' => ['required', 'in:5,10,all'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function ensureTraining(Quiz $quiz): void
    {
        abort_unless($quiz->type === 'training', 404);
    }
}
