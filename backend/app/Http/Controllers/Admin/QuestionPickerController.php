<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\TrainingCategory;
use App\Models\TrainingSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Selettore opzionale per i quiz One Shot e Midalario: invece di scrivere la domanda
 * con le 4 risposte, l'admin la sceglie tra quelle gia' presenti nei Training
 * (filtri a tendina + ricerca) e viene COPIATA nel quiz, media compresi.
 * Le domande dei Training restano dove sono: la copia e' indipendente.
 */
class QuestionPickerController extends Controller
{
    private const PER_PAGE = 40;

    public function index(Request $request, string $quiz)
    {
        $quiz = $this->targetQuiz($quiz);

        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category');
        $subcategoryId = $request->query('subcategory');
        $trainingId = $request->query('training');
        $origin = $request->query('origin');

        // Solo domande approvate (lo scope globale nasconde le bozze) con esattamente
        // 4 risposte e una corretta: One Shot e Midalario richiedono 4 risposte.
        $questions = Question::query()
            ->whereHas('quiz', function ($q) use ($categoryId, $subcategoryId, $trainingId) {
                $q->where('type', 'training')
                    ->when($categoryId, fn ($q) => $q->where('training_category_id', $categoryId))
                    ->when($subcategoryId, fn ($q) => $q->where('training_subcategory_id', $subcategoryId))
                    ->when($trainingId, fn ($q) => $q->where('id', $trainingId));
            })
            ->has('answers', '=', 4)
            ->whereHas('answers', fn ($q) => $q->where('is_correct', true))
            ->when($origin === 'ai', fn ($q) => $q->where('source', Question::SOURCE_AI))
            ->when($origin === 'admin', fn ($q) => $q->where('source', Question::SOURCE_ADMIN))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $q->where(function ($q) use ($like) {
                    $q->where('question_text', 'like', $like)
                        ->orWhereHas('answers', fn ($a) => $a->where('answer_text', 'like', $like));
                });
            })
            ->with(['answers', 'quiz.trainingCategory', 'quiz.trainingSubcategory'])
            ->orderBy('quiz_id')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Domande gia' presenti nel quiz di destinazione (stesso testo normalizzato).
        $existing = Question::withoutGlobalScope('approved')
            ->where('quiz_id', $quiz->id)
            ->pluck('question_text')
            ->map(fn ($text) => $this->normalize($text))
            ->flip();

        $trainings = Quiz::where('type', 'training')
            ->orderBy('title')
            ->get(['id', 'title', 'training_category_id', 'training_subcategory_id']);

        return view('admin.questions.picker', [
            'quiz' => $quiz,
            'questions' => $questions,
            'existing' => $existing,
            'categories' => TrainingCategory::orderBy('name')->get(),
            'subcategories' => TrainingSubcategory::orderBy('name')->get(['id', 'training_category_id', 'name']),
            'trainings' => $trainings,
            'filters' => compact('search', 'categoryId', 'subcategoryId', 'trainingId', 'origin'),
            'normalize' => fn ($text) => $this->normalize($text),
            'quizQuestionsCount' => $quiz->questions()->count(),
        ]);
    }

    public function store(Request $request, string $quiz)
    {
        $quiz = $this->targetQuiz($quiz);

        $data = $request->validate([
            'question_ids' => 'required|array|min:1|max:200',
            'question_ids.*' => 'integer',
            'time_limit_seconds' => 'nullable|integer|min:5|max:600',
        ], [
            'question_ids.required' => 'Seleziona almeno una domanda.',
        ]);

        if ($quiz->isMidalario() && $quiz->midalario_status === 'running') {
            return back()->withErrors(['question_ids' => 'Il Midalario e\' in corso: non si possono aggiungere domande adesso.']);
        }

        $sources = Question::query()
            ->whereIn('id', $data['question_ids'])
            ->whereHas('quiz', fn ($q) => $q->where('type', 'training'))
            ->with('answers')
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $data['question_ids']));

        $seen = Question::withoutGlobalScope('approved')
            ->where('quiz_id', $quiz->id)
            ->pluck('question_text')
            ->map(fn ($text) => $this->normalize($text))
            ->flip()
            ->all();

        $added = 0;
        $skipped = 0;
        $copiedFiles = [];

        try {
            DB::transaction(function () use ($sources, $quiz, $data, &$seen, &$added, &$skipped, &$copiedFiles) {
                foreach ($sources as $source) {
                    $key = $this->normalize($source->question_text);

                    if (isset($seen[$key]) || $source->answers->count() !== 4 || $source->answers->where('is_correct', true)->count() < 1) {
                        $skipped++;
                        continue;
                    }
                    $seen[$key] = true;

                    $copy = Question::create([
                        'quiz_id' => $quiz->id,
                        'source' => $source->source,
                        'review_status' => Question::STATUS_APPROVED,
                        'question_text' => $source->question_text,
                        'time_limit_seconds' => $data['time_limit_seconds'] ?? $source->time_limit_seconds,
                        'audio_source' => $source->audio_source,
                        'itunes_track_id' => $source->itunes_track_id,
                        'itunes_track_name' => $source->itunes_track_name,
                        'itunes_artist_name' => $source->itunes_artist_name,
                        'itunes_preview_url' => $source->itunes_preview_url,
                        'audio_start_seconds' => $source->audio_start_seconds,
                        'audio_end_seconds' => $source->audio_end_seconds,
                        'image_path' => $this->copyFile($source->image_path, $copiedFiles),
                        'audio_path' => $this->copyFile($source->audio_path, $copiedFiles),
                        'video_path' => $this->copyFile($source->video_path, $copiedFiles),
                    ]);

                    foreach ($source->answers->sortBy('id') as $answer) {
                        Answer::create([
                            'question_id' => $copy->id,
                            'answer_text' => $answer->answer_text,
                            'is_correct' => $answer->is_correct,
                        ]);
                    }

                    $added++;
                }
            });
        } catch (\Throwable $e) {
            // Se qualcosa va storto non lasciamo file copiati senza domanda.
            foreach ($copiedFiles as $path) {
                Storage::disk('public')->delete($path);
            }
            report($e);

            return back()->withErrors(['question_ids' => 'Errore durante la copia delle domande: nessuna domanda aggiunta.']);
        }

        $message = "Aggiunte {$added} " . ($added === 1 ? 'domanda' : 'domande') . " al quiz.";
        if ($skipped > 0) {
            $message .= " Saltate {$skipped} (gia' presenti nel quiz o non valide).";
        }

        return redirect()
            ->route('admin.quizzes.questions.index', $quiz->id)
            ->with('success', $message);
    }

    /** Solo One Shot e Midalario: i Training hanno gia' la loro creazione. */
    private function targetQuiz(string $id): Quiz
    {
        $quiz = Quiz::findOrFail($id);
        abort_if($quiz->type === 'training', 404);

        return $quiz;
    }

    /** Copia fisica del file, cosi' eliminare una domanda non rompe l'altra. */
    private function copyFile(?string $path, array &$copied): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $new = 'questions/' . Str::uuid() . ($extension ? ".{$extension}" : '');
        Storage::disk('public')->copy($path, $new);
        $copied[] = $new;

        return $new;
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::of($text)->ascii()->lower()->toString()));
    }
}
