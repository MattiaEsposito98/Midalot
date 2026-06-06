<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TrainingQuestionReportMail;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TrainingReportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'quiz_id' => ['required', 'exists:quizzes,id'],
            'question_id' => ['required', 'exists:questions,id'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $quiz = Quiz::where('type', 'training')
            ->with('trainingCategory:id,name,slug')
            ->findOrFail($validated['quiz_id']);

        $question = Question::where('quiz_id', $quiz->id)
            ->findOrFail($validated['question_id']);

        $user = Auth::guard('sanctum')->user();

        $report = [
            'message' => $validated['message'],
            'nickname' => $user?->nickname ?? 'Ospite',
            'user_email' => $user?->email,
            'quiz_title' => $quiz->title,
            'category_name' => $quiz->trainingCategory?->name,
            'category_slug' => $quiz->trainingCategory?->slug,
            'question_text' => $question->question_text,
            'quiz_id' => $quiz->id,
            'question_id' => $question->id,
        ];

        Mail::to(config('mail.reports_to'))->send(new TrainingQuestionReportMail($report));

        return response()->json([
            'message' => 'Segnalazione inviata. Grazie per il tuo aiuto!',
        ]);
    }
}
