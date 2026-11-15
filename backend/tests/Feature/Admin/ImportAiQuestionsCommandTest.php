<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class ImportAiQuestionsCommandTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    private function writeJson(array $items): string
    {
        $path = tempnam(sys_get_temp_dir(), 'aiq');
        file_put_contents($path, json_encode($items));

        return $path;
    }

    private function item(string $text = 'Qual e la capitale della Francia?', array $extra = []): array
    {
        return array_merge([
            'quiz' => 'Training Anime',
            'question' => $text,
            'answers' => ['Parigi', 'Lione', 'Marsiglia', 'Nizza'],
            'correct' => 0,
            'note' => 'Verifica',
        ], $extra);
    }

    public function test_dry_run_non_scrive_nulla(): void
    {
        $quiz = $this->createTrainingQuiz(5);
        $file = $this->writeJson([$this->item()]);

        $this->artisan('app:import-ai-questions', ['file' => $file, '--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN')
            ->assertSuccessful();

        $this->assertSame(5, Question::withoutGlobalScope('approved')->where('quiz_id', $quiz->id)->count());
    }

    public function test_importa_come_bozze_ai_con_una_sola_risposta_corretta(): void
    {
        $quiz = $this->createTrainingQuiz(5);
        $file = $this->writeJson([$this->item()]);

        $this->artisan('app:import-ai-questions', ['file' => $file])->assertSuccessful();

        $q = Question::withoutGlobalScope('approved')->where('question_text', 'Qual e la capitale della Francia?')->with('answers')->firstOrFail();
        $this->assertSame('ai', $q->source);
        $this->assertSame('pending', $q->review_status);
        $this->assertSame('Verifica', $q->ai_note);
        $this->assertSame(10, $q->time_limit_seconds);
        $this->assertCount(4, $q->answers);
        $this->assertSame(['Parigi'], $q->answers->where('is_correct', true)->pluck('answer_text')->all());

        // Le bozze non contano per il gioco.
        $this->assertSame(5, $quiz->questions()->count());
    }

    public function test_scarta_i_doppioni_con_il_gioco_gia_presente_e_nel_file(): void
    {
        $quiz = $this->createTrainingQuiz(5); // "Question 1".. con risposte Correct/Wrong 1-3
        $file = $this->writeJson([
            $this->item('  QUESTION 1?  '),                          // stesso testo normalizzato
            $this->item('Altra formulazione', ['answers' => ['Correct', 'Wrong 1', 'Wrong 2', 'Wrong 3'], 'correct' => 0]), // stesse risposte
            $this->item('Domanda nuova'),
            $this->item('domanda nuova!'),                            // doppione dentro il file
        ]);

        $this->artisan('app:import-ai-questions', ['file' => $file])
            ->expectsOutputToContain('Nuove bozze: 1 | scartate come doppioni: 3')
            ->assertSuccessful();

        $this->assertSame(1, Question::withoutGlobalScope('approved')->where('source', 'ai')->count());
    }

    public function test_stessa_risposta_corretta_diversa_non_e_un_doppione_se_le_risposte_cambiano(): void
    {
        $this->createTrainingQuiz(1);
        $file = $this->writeJson([
            $this->item('Domanda A'),
            $this->item('Domanda B', ['answers' => ['Parigi', 'Roma', 'Madrid', 'Berlino']]),
        ]);

        $this->artisan('app:import-ai-questions', ['file' => $file])
            ->expectsOutputToContain('Nuove bozze: 2 | scartate come doppioni: 0')
            ->assertSuccessful();
    }

    public function test_un_errore_nel_file_blocca_tutto_senza_scrivere(): void
    {
        $this->createTrainingQuiz(1);
        $file = $this->writeJson([
            $this->item('Valida'),
            $this->item('Con 3 risposte', ['answers' => ['a', 'b', 'c']]),
            $this->item('Quiz inesistente', ['quiz' => 'Non esiste']),
            $this->item('Corretta fuori range', ['correct' => 4]),
            $this->item('Risposte uguali', ['answers' => ['Si', 'si', 'No', 'Forse']]),
        ]);

        $this->artisan('app:import-ai-questions', ['file' => $file])->assertFailed();

        $this->assertSame(0, Question::withoutGlobalScope('approved')->where('source', 'ai')->count());
    }
}
