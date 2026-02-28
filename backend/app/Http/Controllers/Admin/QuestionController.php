<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $quiz)
    {
        $quiz = Quiz::findOrFail($quiz);

        $questions = $quiz->questions()
            ->orderBy('order')
            ->get();

        return view('admin.questions.index', compact('quiz', 'questions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(string $quiz)
    {
        $quiz = Quiz::findOrFail($quiz);

        return view('admin.questions.create', compact('quiz'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $quiz)
    {
        $quiz = Quiz::findOrFail($quiz);

        $request->validate([
            'question_text' => 'required|string',
            'time_limit_seconds' => 'required|integer|min:5',
            'order' => 'required|integer|min:1',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'audio' => 'nullable|mimes:mp3,wav,ogg|max:5120',
            'video' => 'nullable|mimes:mp4,mov,webm|max:20000',
        ]);

        $data = [
            'quiz_id' => $quiz->id,
            'question_text' => $request->question_text,
            'time_limit_seconds' => $request->time_limit_seconds,
            'order' => $request->order,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store('questions', 'public');
        }

        if ($request->hasFile('audio')) {
            $data['audio_path'] = $request->file('audio')
                ->store('questions', 'public');
        }

        if ($request->hasFile('video')) {
            $data['video_path'] = $request->file('video')
                ->store('questions', 'public');
        }

        Question::create($data);

        return redirect()
            ->route('admin.quizzes.questions.index', $quiz->id)
            ->with('success', 'Domanda creata con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $quiz, string $question)
    {
        $quiz = Quiz::findOrFail($quiz);

        $question = Question::where('quiz_id', $quiz->id)
            ->findOrFail($question);

        return view('admin.questions.edit', compact('quiz', 'question'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $quiz, string $question)
    {
        $quiz = Quiz::findOrFail($quiz);

        $question = Question::where('quiz_id', $quiz->id)
            ->findOrFail($question);

        $request->validate([
            'question_text' => 'required|string',
            'time_limit_seconds' => 'required|integer|min:5',
            'order' => 'required|integer|min:1',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'audio' => 'nullable|mimes:mp3,wav,ogg|max:5120',
            'video' => 'nullable|mimes:mp4,mov,webm|max:20000',
        ]);

        $data = [
            'question_text' => $request->question_text,
            'time_limit_seconds' => $request->time_limit_seconds,
            'order' => $request->order,
        ];

        // IMAGE
        if ($request->hasFile('image')) {
            if ($question->image_path) {
                Storage::disk('public')->delete($question->image_path);
            }

            $data['image_path'] = $request->file('image')
                ->store('questions', 'public');
        }

        // AUDIO
        if ($request->hasFile('audio')) {
            if ($question->audio_path) {
                Storage::disk('public')->delete($question->audio_path);
            }

            $data['audio_path'] = $request->file('audio')
                ->store('questions', 'public');
        }

        // VIDEO
        if ($request->hasFile('video')) {
            if ($question->video_path) {
                Storage::disk('public')->delete($question->video_path);
            }

            $data['video_path'] = $request->file('video')
                ->store('questions', 'public');
        }

        $question->update($data);

        return redirect()
            ->route('admin.quizzes.questions.index', $quiz->id)
            ->with('success', 'Domanda aggiornata!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $quiz, string $question)
    {
        $quiz = Quiz::findOrFail($quiz);

        $question = Question::where('quiz_id', $quiz->id)
            ->findOrFail($question);

        // Cancella file collegati
        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }

        if ($question->audio_path) {
            Storage::disk('public')->delete($question->audio_path);
        }

        if ($question->video_path) {
            Storage::disk('public')->delete($question->video_path);
        }

        $question->delete();

        return back()->with('success', 'Domanda eliminata!');
    }
}
