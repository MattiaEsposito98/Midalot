<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa domande generate dall'AI come BOZZE (review_status = pending, source = ai).
 * L'admin le vede in "Domande da confermare" e decide se attivarle.
 *
 * Formato del file JSON: lista di oggetti
 *   { "quiz": "Titolo esatto del Training", "question": "...", "answers": ["a","b","c","d"],
 *     "correct": 0, "note": "cosa verificare (facoltativo)", "time": 10 (facoltativo) }
 * "correct" e' l'indice (0-3) della risposta giusta dentro "answers".
 */
class ImportAiQuestions extends Command
{
    protected $signature = 'app:import-ai-questions {file : Percorso del file JSON} {--dry-run : Mostra il report senza scrivere nulla}';

    protected $description = 'Importa domande AI come bozze nei Training, scartando i doppioni';

    public function handle(): int
    {
        $file = $this->argument('file');
        $dry = (bool) $this->option('dry-run');

        if (! is_file($file)) {
            $this->error("File non trovato: {$file}");

            return self::FAILURE;
        }

        $items = json_decode((string) file_get_contents($file), true);
        if (! is_array($items)) {
            $this->error('JSON non valido.');

            return self::FAILURE;
        }

        // Il quiz si riconosce dal titolo (gli id cambiano tra locale e produzione).
        $quizzes = [];
        $errors = [];
        foreach ($items as $i => $item) {
            $n = $i + 1;
            $title = trim((string) ($item['quiz'] ?? ''));
            if (! isset($quizzes[$title])) {
                $found = Quiz::where('type', 'training')->where('title', $title)->get();
                if ($found->count() !== 1) {
                    $errors[] = "#{$n}: quiz \"{$title}\" " . ($found->isEmpty() ? 'non trovato' : 'ambiguo');
                    $quizzes[$title] = null;
                    continue;
                }
                $quizzes[$title] = $found->first();
            }
            if ($quizzes[$title] === null) {
                continue;
            }
            if ($msg = $this->validationError($item)) {
                $errors[] = "#{$n} ({$title}): {$msg}";
            }
        }

        if ($errors) {
            $this->error('Errori nel file, nessuna scrittura:');
            foreach ($errors as $e) {
                $this->line(" - {$e}");
            }

            return self::FAILURE;
        }

        // Chiavi dei doppioni, per quiz: testo normalizzato e insieme risposte+corretta.
        $seen = [];
        $existingCount = [];
        $toInsert = [];
        $skipped = [];

        foreach ($items as $i => $item) {
            $quiz = $quizzes[trim($item['quiz'])];
            $qid = $quiz->id;

            if (! isset($seen[$qid])) {
                $seen[$qid] = ['text' => [], 'set' => []];
                $existingCount[$qid] = $quiz->questions()->count();
                $existing = Question::withoutGlobalScope('approved')
                    ->where('quiz_id', $qid)->with('answers')->get();
                foreach ($existing as $q) {
                    [$tk, $sk] = $this->keys($q->question_text, $q->answers->pluck('answer_text')->all(), optional($q->answers->firstWhere('is_correct', true))->answer_text);
                    $seen[$qid]['text'][$tk] = true;
                    $seen[$qid]['set'][$sk] = true;
                }
            }

            $correctText = $item['answers'][$item['correct']];
            [$tk, $sk] = $this->keys($item['question'], $item['answers'], $correctText);

            if (isset($seen[$qid]['text'][$tk])) {
                $skipped[] = [$quiz->title, $item['question'], 'stesso testo di una domanda gia presente'];
                continue;
            }
            if (isset($seen[$qid]['set'][$sk])) {
                $skipped[] = [$quiz->title, $item['question'], 'stesse risposte (e stessa corretta) di una domanda gia presente'];
                continue;
            }

            $seen[$qid]['text'][$tk] = true;
            $seen[$qid]['set'][$sk] = true;
            $toInsert[] = [$quiz, $item];
        }

        if (! $dry) {
            DB::transaction(function () use ($toInsert) {
                foreach ($toInsert as [$quiz, $item]) {
                    $question = Question::create([
                        'quiz_id' => $quiz->id,
                        'source' => Question::SOURCE_AI,
                        'review_status' => Question::STATUS_PENDING,
                        'ai_note' => $item['note'] ?? null,
                        'question_text' => trim($item['question']),
                        'time_limit_seconds' => (int) ($item['time'] ?? 10),
                    ]);

                    // Ordine delle risposte mescolato, cosi' la corretta non e' sempre la prima.
                    $order = array_keys($item['answers']);
                    shuffle($order);
                    foreach ($order as $idx) {
                        Answer::create([
                            'question_id' => $question->id,
                            'answer_text' => trim($item['answers'][$idx]),
                            'is_correct' => $idx === $item['correct'],
                        ]);
                    }
                }
            });
        }

        $this->report($toInsert, $skipped, $existingCount, $dry);

        return self::SUCCESS;
    }

    private function validationError(array $item): ?string
    {
        $text = trim((string) ($item['question'] ?? ''));
        $answers = $item['answers'] ?? null;

        if ($text === '') {
            return 'testo domanda vuoto';
        }
        if (! is_array($answers) || count($answers) !== 4) {
            return 'servono esattamente 4 risposte';
        }
        $norm = [];
        foreach ($answers as $a) {
            $a = trim((string) $a);
            if ($a === '' || mb_strlen($a) > 255) {
                return 'risposta vuota o oltre 255 caratteri';
            }
            $norm[] = $this->normalize($a);
        }
        if (count(array_unique($norm)) !== 4) {
            return 'risposte duplicate tra loro';
        }
        if (! isset($item['correct']) || ! is_int($item['correct']) || $item['correct'] < 0 || $item['correct'] > 3) {
            return '"correct" deve essere un indice da 0 a 3';
        }
        if (isset($item['time']) && (! is_int($item['time']) || $item['time'] < 5)) {
            return '"time" deve essere un intero >= 5';
        }

        return null;
    }

    private function normalize(string $s): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::of($s)->ascii()->lower()->toString()));
    }

    /** @return array{0:string,1:string} chiave testo, chiave insieme risposte */
    private function keys(string $text, array $answers, ?string $correct): array
    {
        $norm = array_map(fn ($a) => $this->normalize((string) $a), $answers);
        sort($norm);

        return [$this->normalize($text), implode('|', $norm) . '=>' . $this->normalize((string) $correct)];
    }

    private function report(array $toInsert, array $skipped, array $existingCount, bool $dry): void
    {
        $perQuiz = [];
        foreach ($toInsert as [$quiz]) {
            $perQuiz[$quiz->id]['title'] = $quiz->title;
            $perQuiz[$quiz->id]['new'] = ($perQuiz[$quiz->id]['new'] ?? 0) + 1;
        }

        $rows = [];
        foreach ($perQuiz as $qid => $row) {
            $rows[] = [$row['title'], $existingCount[$qid], $row['new'], $existingCount[$qid] + $row['new']];
        }

        $this->info($dry ? 'DRY-RUN: nessuna scrittura.' : 'Importate come bozze (in attesa di conferma).');
        $this->table(['Quiz', 'Attive ora', 'Nuove bozze', 'Totale se attivate'], $rows);
        $this->line('Nuove bozze: ' . count($toInsert) . ' | scartate come doppioni: ' . count($skipped));

        foreach ($skipped as [$title, $text, $why]) {
            $this->warn(" scartata [{$title}] {$text} -> {$why}");
        }
    }
}
