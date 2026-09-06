<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\TrainingAnswer;
use App\Models\TrainingAttempt;
use App\Models\TrainingCategory;
use App\Models\TrainingSubcategory;
use App\Services\AnswerTimer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TrainingController extends Controller
{
    private const CORRECT_ANSWER_BASE_SCORE = 7000;

    private const MAX_SPEED_BONUS = 3000;

    private const WRONG_ANSWER_PENALTY_RATE = 0.10;

    private const TIMEOUT_PENALTY_RATE = 0.05;

    public function categories()
    {
        $activeTrainingQuiz = fn ($query) => $query->where('type', 'training')->where('is_active', true);

        $categories = TrainingCategory::where('is_active', true)
            ->whereHas('quizzes', $activeTrainingQuiz)
            ->withCount(['quizzes' => $activeTrainingQuiz])
            ->with(['subcategories' => function ($query) use ($activeTrainingQuiz) {
                $query->where('is_active', true)
                    ->whereHas('quizzes', $activeTrainingQuiz)
                    ->withCount(['quizzes' => $activeTrainingQuiz])
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image' => $category->image_url,
                'quizzes_count' => $category->quizzes_count,
                'subcategories' => $category->subcategories->map(fn ($subcategory) => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                    'image' => $subcategory->image_url,
                    'quizzes_count' => $subcategory->quizzes_count,
                ])->values(),
            ]);

        return response()->json(['categories' => $categories]);
    }

    public function categoryQuizzes(Request $request, string $categorySlug)
    {
        $category = TrainingCategory::where('slug', $categorySlug)
            ->where('is_active', true)
            ->firstOrFail();

        $activeTrainingQuiz = fn ($query) => $query->where('type', 'training')->where('is_active', true);

        $subcategories = TrainingSubcategory::where('training_category_id', $category->id)
            ->where('is_active', true)
            ->whereHas('quizzes', $activeTrainingQuiz)
            ->withCount(['quizzes' => $activeTrainingQuiz])
            ->orderBy('name')
            ->get();

        $selectedSubcategorySlug = $request->query('subcategory');
        $selectedSubcategory = null;

        $quizzesQuery = Quiz::where('type', 'training')
            ->where('training_category_id', $category->id)
            ->where('is_active', true)
            ->with('trainingSubcategory')
            ->withCount('questions');

        if ($selectedSubcategorySlug) {
            $selectedSubcategory = TrainingSubcategory::where('training_category_id', $category->id)
                ->where('slug', $selectedSubcategorySlug)
                ->firstOrFail();

            $quizzesQuery->where('training_subcategory_id', $selectedSubcategory->id);
        }

        $quizzes = $quizzesQuery
            ->latest()
            ->get()
            ->filter(fn ($quiz) => $this->isPlayable($quiz))
            ->values()
            ->map(fn ($quiz) => $this->quizSummary($quiz));

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'subcategories' => $subcategories->map(fn ($subcategory) => [
                'id' => $subcategory->id,
                'name' => $subcategory->name,
                'slug' => $subcategory->slug,
                'image' => $subcategory->image_url,
                'quizzes_count' => $subcategory->quizzes_count,
            ])->values(),
            'selected_subcategory' => $selectedSubcategory?->slug,
            'quizzes' => $quizzes,
        ]);
    }

    public function show(Quiz $quiz)
    {
        $this->ensurePlayableTraining($quiz);

        $quiz->loadCount('questions');

        return response()->json([
            'quiz' => $this->quizSummary($quiz),
        ]);
    }

    public function guestStart(Quiz $quiz)
    {
        $this->ensurePlayableTraining($quiz);

        $questions = $this->selectQuestions($quiz);
        $token = Str::uuid()->toString();

        Cache::put($this->guestCacheKey($token), [
            'quiz_id' => $quiz->id,
            'question_ids' => $questions->pluck('id')->values()->all(),
            'answers' => [],
            'started_at' => now()->toISOString(),
        ], now()->addHours(2));

        return response()->json([
            'session_token' => $token,
            'quiz' => $this->quizPayload($quiz, $questions),
        ]);
    }

    public function guestAnswer(Request $request)
    {
        $validated = $request->validate([
            'session_token' => ['required', 'string'],
            'question_id' => ['required', 'exists:questions,id'],
            'answer_id' => ['nullable', 'exists:answers,id'],
            'time_taken' => ['required', 'integer', 'min:0'],
        ]);

        $state = Cache::get($this->guestCacheKey($validated['session_token']));

        if (! $state) {
            return response()->json(['message' => 'Sessione training scaduta'], 404);
        }

        // Per gli ospiti l'ultimo istante osservato vive nella cache di sessione,
        // non essendoci righe a database.
        $lastEventAt = Carbon::parse($state['last_answer_at'] ?? $state['started_at']);

        $result = $this->scoreAnswer($state['quiz_id'], $state['question_ids'], $state['answers'], $validated, $lastEventAt);
        $state['answers'][(string) $validated['question_id']] = $result['stored'];
        $state['last_answer_at'] = now()->toISOString();

        Cache::put($this->guestCacheKey($validated['session_token']), $state, now()->addHours(2));

        return response()->json($result['response']);
    }

    public function guestFinish(Request $request)
    {
        $validated = $request->validate([
            'session_token' => ['required', 'string'],
        ]);

        $state = Cache::pull($this->guestCacheKey($validated['session_token']));

        if (! $state) {
            return response()->json(['message' => 'Sessione training scaduta'], 404);
        }

        return response()->json([
            ...$this->finalResult(collect($state['answers'])),
            'saved' => false,
            'message' => 'Registrati per salvare le tue sessioni di training e vedere i tuoi progressi.',
        ]);
    }

    public function start(Request $request, Quiz $quiz)
    {
        $this->ensurePlayableTraining($quiz);

        $questions = $this->selectQuestions($quiz);

        $attempt = TrainingAttempt::create([
            'quiz_id' => $quiz->id,
            'training_category_id' => $quiz->training_category_id,
            'user_id' => $request->user()->id,
            'question_ids' => $questions->pluck('id')->values()->all(),
            'started_at' => now(),
            'total_questions' => $questions->count(),
            'completed' => false,
        ]);

        return response()->json([
            'attempt_id' => $attempt->id,
            'quiz' => $this->quizPayload($quiz, $questions),
        ], 201);
    }

    public function answer(Request $request)
    {
        $validated = $request->validate([
            'attempt_id' => ['required', 'exists:training_attempts,id'],
            'question_id' => ['required', 'exists:questions,id'],
            'answer_id' => ['nullable', 'exists:answers,id'],
            'time_taken' => ['required', 'integer', 'min:0'],
        ]);

        $attempt = TrainingAttempt::findOrFail($validated['attempt_id']);

        if ((int) $attempt->user_id !== (int) $request->user()->id || $attempt->completed) {
            return response()->json(['message' => 'Tentativo training non valido'], 403);
        }

        $existingAnswers = $attempt->answers()
            ->pluck('score', 'question_id')
            ->map(fn ($score) => ['score' => $score])
            ->toArray();

        $lastAnsweredAt = $attempt->answers()->max('created_at');
        $lastEventAt = $lastAnsweredAt ? Carbon::parse($lastAnsweredAt) : $attempt->started_at;

        $result = $this->scoreAnswer($attempt->quiz_id, $attempt->question_ids, $existingAnswers, $validated, $lastEventAt);
        $stored = $result['stored'];

        TrainingAnswer::create([
            'training_attempt_id' => $attempt->id,
            'question_id' => $validated['question_id'],
            'answer_id' => $stored['answer_id'],
            'time_taken' => $stored['time_taken'],
            'is_correct' => $stored['is_correct'],
            'is_timeout' => $stored['is_timeout'],
            'is_wrong' => $stored['is_wrong'],
            'score' => $stored['score'],
        ]);

        return response()->json($result['response']);
    }

    public function finish(Request $request)
    {
        $validated = $request->validate([
            'attempt_id' => ['required', 'exists:training_attempts,id'],
        ]);

        $attempt = TrainingAttempt::with('answers')->findOrFail($validated['attempt_id']);

        if ((int) $attempt->user_id !== (int) $request->user()->id || $attempt->completed) {
            return response()->json(['message' => 'Tentativo training non valido'], 403);
        }

        $result = $this->finalResult($attempt->answers);

        $attempt->update([
            'score' => $result['score'],
            'total_time' => $result['total_time'],
            'correct_answers' => $result['correct_answers'],
            'total_questions' => count($attempt->question_ids),
            'completed' => true,
            'finished_at' => now(),
        ]);

        return response()->json([
            ...$result,
            'saved' => true,
        ]);
    }

    public function progress(Request $request)
    {
        $attempts = TrainingAttempt::with(['category', 'quiz'])
            ->where('user_id', $request->user()->id)
            ->where('completed', true)
            ->latest('finished_at')
            ->get();

        $categories = $attempts
            ->groupBy('training_category_id')
            ->map(function ($items) {
                $best = $items->sortByDesc('score')->first();

                return [
                    'category' => [
                        'id' => $best->category->id,
                        'name' => $best->category->name,
                        'slug' => $best->category->slug,
                    ],
                    'attempts_count' => $items->count(),
                    'best_score' => $items->max('score'),
                    'best_correct_answers' => $best->correct_answers,
                    'best_total_questions' => $best->total_questions,
                    'average_score' => round($items->avg('score')),
                ];
            })
            ->values();

        $recentAttempts = TrainingAttempt::with(['category', 'quiz'])
            ->where('user_id', $request->user()->id)
            ->where('completed', true)
            ->latest('finished_at')
            ->limit(8)
            ->get();

        return response()->json([
            'categories' => $categories,
            'recent_attempts' => $recentAttempts->map(fn ($attempt) => $this->attemptPayload($attempt))->values(),
        ]);
    }

    public function leaderboard(string $categorySlug)
    {
        $category = TrainingCategory::where('slug', $categorySlug)->firstOrFail();

        $attempts = TrainingAttempt::with('user.latestMonthlyBadge')
            ->where('training_category_id', $category->id)
            ->where('completed', true)
            ->orderByDesc('score')
            ->orderBy('total_time')
            ->limit(50)
            ->get();

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'results' => $attempts->map(fn ($attempt, $index) => [
                'position' => $index + 1,
                'nickname' => $attempt->user->nickname ?? $attempt->user->name,
                'badge' => $attempt->user->latestMonthlyBadge?->label,
                'score' => $attempt->score,
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions,
                'total_time' => $attempt->total_time,
                'finished_at' => $attempt->finished_at?->toISOString(),
            ]),
        ]);
    }

    public function quizLeaderboard(Quiz $quiz)
    {
        abort_unless($quiz->type === 'training', 404);

        if (! $quiz->leaderboard_visible) {
            return response()->json([
                'message' => 'Classifica non disponibile',
            ], 403);
        }

        $quiz->loadMissing('trainingCategory');

        $attempts = TrainingAttempt::with('user.latestMonthlyBadge')
            ->where('quiz_id', $quiz->id)
            ->where('completed', true)
            ->orderByDesc('score')
            ->orderBy('total_time')
            ->limit(50)
            ->get();

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'category' => [
                    'id' => $quiz->trainingCategory->id,
                    'name' => $quiz->trainingCategory->name,
                    'slug' => $quiz->trainingCategory->slug,
                ],
            ],
            'results' => $attempts->map(fn ($attempt, $index) => [
                'position' => $index + 1,
                'nickname' => $attempt->user->nickname ?? $attempt->user->name,
                'badge' => $attempt->user->latestMonthlyBadge?->label,
                'score' => $attempt->score,
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions,
                'total_time' => $attempt->total_time,
                'finished_at' => $attempt->finished_at?->toISOString(),
            ]),
        ]);
    }

    private function isPlayable(Quiz $quiz): bool
    {
        $questionsCount = $quiz->questions_count ?? $quiz->questions()->count();

        if (! $quiz->is_active || $quiz->type !== 'training' || ! $quiz->trainingCategory?->is_active) {
            return false;
        }

        if ($quiz->training_question_mode === 'all') {
            return $questionsCount > 0;
        }

        return $questionsCount >= (int) $quiz->training_question_mode;
    }

    private function ensurePlayableTraining(Quiz $quiz): void
    {
        $quiz->loadMissing('trainingCategory');
        $quiz->loadCount('questions');

        abort_unless($this->isPlayable($quiz), 404);
    }

    private function selectQuestions(Quiz $quiz)
    {
        $limit = $quiz->training_question_mode === 'all' ? null : (int) $quiz->training_question_mode;

        $query = $quiz->questions()
            ->with('answers:id,question_id,answer_text')
            ->inRandomOrder();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function quizSummary(Quiz $quiz): array
    {
        $quiz->loadMissing('trainingSubcategory');

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'image' => $quiz->image_url,
            'category' => [
                'id' => $quiz->trainingCategory->id,
                'name' => $quiz->trainingCategory->name,
                'slug' => $quiz->trainingCategory->slug,
            ],
            'subcategory' => $quiz->trainingSubcategory ? [
                'id' => $quiz->trainingSubcategory->id,
                'name' => $quiz->trainingSubcategory->name,
                'slug' => $quiz->trainingSubcategory->slug,
            ] : null,
            'question_mode' => $quiz->training_question_mode,
            'questions_count' => $quiz->questions_count,
            'leaderboard_visible' => (bool) $quiz->leaderboard_visible,
        ];
    }

    private function quizPayload(Quiz $quiz, $questions): array
    {
        return [
            ...$this->quizSummary($quiz->loadMissing('trainingCategory')->loadCount('questions')),
            'total_questions' => $questions->count(),
            'questions' => $questions->map(fn ($question) => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'image' => $question->image_path ? asset('storage/'.$question->image_path) : null,
                'audio' => $question->resolvedAudioUrl(),
                'audio_start_seconds' => $question->audio_start_seconds,
                'audio_end_seconds' => $question->audio_end_seconds,
                'video' => $question->video_path ? asset('storage/'.$question->video_path) : null,
                'time_limit_seconds' => $question->time_limit_seconds,
                'answers' => $question->answers->map(fn ($answer) => [
                    'id' => $answer->id,
                    'answer_text' => $answer->answer_text,
                ])->values(),
            ])->values(),
        ];
    }

    private function scoreAnswer(int $quizId, array $questionIds, array $existingAnswers, array $data, ?Carbon $lastEventAt = null): array
    {
        if (! in_array((int) $data['question_id'], array_map('intval', $questionIds), true)) {
            throw new HttpResponseException(response()->json(['message' => 'Domanda non valida per questo training'], 422));
        }

        if (array_key_exists((string) $data['question_id'], $existingAnswers) || array_key_exists((int) $data['question_id'], $existingAnswers)) {
            throw new HttpResponseException(response()->json(['message' => 'Hai gia risposto a questa domanda'], 403));
        }

        $question = Question::where('quiz_id', $quizId)->findOrFail($data['question_id']);
        $correctAnswerId = (int) $question->answers()
            ->where('is_correct', true)
            ->value('id');
        $maxTimeMs = (int) $question->time_limit_seconds * 1000;
        $timeTaken = AnswerTimer::resolve((int) $data['time_taken'], $lastEventAt, $maxTimeMs);
        $answerId = null;
        $isCorrect = false;
        $isTimeout = false;
        $isWrong = false;
        $score = 0;
        $currentScore = max(0, (int) collect($existingAnswers)->sum('score'));

        if (($data['answer_id'] ?? null) === null) {
            $isTimeout = true;
            $score = -$this->calculatePenalty($currentScore, self::TIMEOUT_PENALTY_RATE);
        } else {
            $answer = Answer::where('id', $data['answer_id'])
                ->where('question_id', $question->id)
                ->first();

            if (! $answer) {
                throw new HttpResponseException(response()->json(['message' => 'Risposta non valida'], 422));
            }

            $answerId = $answer->id;
            $isCorrect = (bool) $answer->is_correct;

            if ($isCorrect) {
                $speedBonus = (int) round((($maxTimeMs - $timeTaken) / $maxTimeMs) * self::MAX_SPEED_BONUS);
                $score = self::CORRECT_ANSWER_BASE_SCORE + $speedBonus;
            } else {
                $isWrong = true;
                $score = -$this->calculatePenalty($currentScore, self::WRONG_ANSWER_PENALTY_RATE);
            }
        }

        $stored = [
            'question_id' => (int) $question->id,
            'answer_id' => $answerId,
            'time_taken' => $timeTaken,
            'is_correct' => $isCorrect,
            'is_timeout' => $isTimeout,
            'is_wrong' => $isWrong,
            'score' => $score,
        ];

        return [
            'stored' => $stored,
            'response' => [
                'correct' => $isCorrect,
                'wrong' => $isWrong,
                'timeout' => $isTimeout,
                'correct_answer_id' => $correctAnswerId,
                'score' => $score,
            ],
        ];
    }

    private function finalResult($answers): array
    {
        return [
            'score' => (int) $answers->sum('score'),
            'total_time' => (int) $answers->sum('time_taken'),
            'correct_answers' => (int) $answers->where('is_correct', true)->count(),
            'answered_questions' => $answers->count(),
        ];
    }

    private function calculatePenalty(int $currentScore, float $penaltyRate): int
    {
        return min($currentScore, (int) round($currentScore * $penaltyRate));
    }

    private function attemptPayload(TrainingAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'quiz_title' => $attempt->quiz->title,
            'category_name' => $attempt->category->name,
            'category_slug' => $attempt->category->slug,
            'score' => $attempt->score,
            'correct_answers' => $attempt->correct_answers,
            'total_questions' => $attempt->total_questions,
            'total_time' => $attempt->total_time,
            'finished_at' => $attempt->finished_at?->toISOString(),
        ];
    }

    private function guestCacheKey(string $token): string
    {
        return 'training_guest_attempt:'.$token;
    }
}
