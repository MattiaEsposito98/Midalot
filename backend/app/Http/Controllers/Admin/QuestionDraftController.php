<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;

class QuestionDraftController extends Controller
{
    /**
     * Tutte le bozze AI in attesa di conferma, raggruppate per quiz.
     * Per attivarle, modificarle o eliminarle si usano le azioni di QuestionController.
     */
    public function index()
    {
        $drafts = Question::withoutGlobalScope('approved')
            ->where('review_status', Question::STATUS_PENDING)
            ->with('answers')
            ->orderBy('quiz_id')
            ->orderBy('id')
            ->get();

        $quizzes = Quiz::with(['trainingCategory', 'trainingSubcategory'])
            ->whereIn('id', $drafts->pluck('quiz_id')->unique())
            ->get()
            ->sortBy(fn (Quiz $quiz) => ($quiz->trainingCategory?->name ?? '') . '|' . $quiz->title);

        $draftsByQuiz = $drafts->groupBy('quiz_id');

        return view('admin.question-drafts.index', [
            'quizzes' => $quizzes,
            'draftsByQuiz' => $draftsByQuiz,
            'total' => $drafts->count(),
        ]);
    }
}
