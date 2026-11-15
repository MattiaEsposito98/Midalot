<?php

namespace Tests\Feature\Admin;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class QuestionPickerTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    private function midalario(string $status = 'open'): Quiz
    {
        return Quiz::create([
            'title' => 'Midalario di prova',
            'type' => 'midalario',
            'midalario_status' => $status,
            'created_by' => $this->createAdmin()->id,
            'is_active' => true,
        ]);
    }

    public function test_la_pagina_e_solo_per_admin_e_solo_per_one_shot_e_midalario(): void
    {
        $oneShot = $this->createAssignedQuiz();
        $training = $this->createTrainingQuiz(1);

        $this->actingAs($this->createUser())
            ->get(route('admin.quizzes.questions.picker', $oneShot->id))
            ->assertStatus(403);

        $admin = $this->createAdmin();
        $this->actingAs($admin)->get(route('admin.quizzes.questions.picker', $oneShot->id))->assertOk();
        $this->actingAs($admin)->get(route('admin.quizzes.questions.picker', $this->midalario()->id))->assertOk();
        $this->actingAs($admin)->get(route('admin.quizzes.questions.picker', $training->id))->assertNotFound();
    }

    public function test_il_pulsante_compare_solo_nei_quiz_non_training(): void
    {
        $admin = $this->createAdmin();
        $oneShot = $this->createAssignedQuiz();
        $training = $this->createTrainingQuiz(1);

        $this->actingAs($admin)->get(route('admin.quizzes.questions.index', $oneShot->id))
            ->assertSee('Pesca dai Training');
        $this->actingAs($admin)->get(route('admin.quizzes.questions.index', $training->id))
            ->assertDontSee('Pesca dai Training');
    }

    public function test_elenca_solo_domande_training_approvate_con_4_risposte(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createAssignedQuiz();
        $training = $this->createTrainingQuiz(2); // "Question 1", "Question 2": 4 risposte

        // domanda Training con sole 3 risposte: esclusa
        $tre = Question::create(['quiz_id' => $training->id, 'question_text' => 'Solo tre risposte', 'time_limit_seconds' => 10]);
        foreach ([['Si', true], ['No', false], ['Forse', false]] as [$t, $c]) {
            Answer::create(['question_id' => $tre->id, 'answer_text' => $t, 'is_correct' => $c]);
        }

        // bozza AI: esclusa
        Question::create(['quiz_id' => $training->id, 'question_text' => 'Bozza nascosta', 'time_limit_seconds' => 10,
            'source' => 'ai', 'review_status' => 'pending']);

        // domanda di un altro One Shot: esclusa
        $this->addQuestions($this->createAssignedQuiz(['created_by' => $admin->id]), 1);

        $response = $this->actingAs($admin)->get(route('admin.quizzes.questions.picker', $target->id))->assertOk();

        $response->assertSee('Question 1')->assertSee('Question 2')
            ->assertDontSee('Solo tre risposte')
            ->assertDontSee('Bozza nascosta');
        $this->assertCount(2, $response->viewData('questions'));
    }

    public function test_filtri_per_testo_categoria_e_training(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createAssignedQuiz();
        $a = $this->createTrainingQuiz(2, ['title' => 'Training A']);
        $b = $this->createTrainingQuiz(2, ['title' => 'Training B', 'category' => \App\Models\TrainingCategory::create([
            'name' => 'Storia', 'slug' => 'storia', 'is_active' => true,
        ])]);
        $b->questions()->first()->update(['question_text' => 'Chi fu Cesare?']);

        $url = fn (array $q) => route('admin.quizzes.questions.picker', ['quiz' => $target->id] + $q);

        $this->assertCount(1, $this->actingAs($admin)->get($url(['search' => 'Cesare']))->viewData('questions'));
        $this->assertCount(2, $this->actingAs($admin)->get($url(['training' => $a->id]))->viewData('questions'));
        $this->assertCount(2, $this->actingAs($admin)->get($url(['category' => $b->training_category_id]))->viewData('questions'));
        $this->assertCount(4, $this->actingAs($admin)->get($url([]))->viewData('questions'));
        // la ricerca guarda anche nelle risposte
        $this->assertCount(4, $this->actingAs($admin)->get($url(['search' => 'Wrong 2']))->viewData('questions'));
    }

    public function test_copia_la_domanda_con_le_risposte_in_modo_indipendente(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createAssignedQuiz();
        $training = $this->createTrainingQuiz(2);
        $source = $training->questions()->orderBy('id')->first();
        $source->update(['source' => 'ai']);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.picker.store', $target->id), ['question_ids' => [$source->id]])
            ->assertRedirect(route('admin.quizzes.questions.index', $target->id))
            ->assertSessionHas('success');

        $copy = Question::where('quiz_id', $target->id)->with('answers')->firstOrFail();
        $this->assertNotSame($source->id, $copy->id);
        $this->assertSame($source->question_text, $copy->question_text);
        $this->assertSame('ai', $copy->source);
        $this->assertSame('approved', $copy->review_status);
        $this->assertCount(4, $copy->answers);
        $this->assertSame(['Correct'], $copy->answers->where('is_correct', true)->pluck('answer_text')->all());

        // l'originale nel Training resta com'e'
        $this->assertSame(2, $training->questions()->count());
        $this->assertCount(4, $source->fresh()->answers);
    }

    public function test_il_timer_facoltativo_sostituisce_quello_originale(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createAssignedQuiz();
        $training = $this->createTrainingQuiz(2);
        $ids = $training->questions()->pluck('id')->all();

        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.picker.store', $target->id), ['question_ids' => $ids, 'time_limit_seconds' => 15])
            ->assertRedirect();

        $this->assertSame([15, 15], Question::where('quiz_id', $target->id)->pluck('time_limit_seconds')->all());

        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.picker.store', $target->id), ['question_ids' => $ids, 'time_limit_seconds' => 3])
            ->assertSessionHasErrors('time_limit_seconds');
    }

    public function test_non_inserisce_due_volte_la_stessa_domanda(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createAssignedQuiz();
        $training = $this->createTrainingQuiz(2);
        $ids = $training->questions()->pluck('id')->all();

        $this->actingAs($admin)->post(route('admin.quizzes.questions.picker.store', $target->id), ['question_ids' => $ids]);
        $second = $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.picker.store', $target->id), ['question_ids' => $ids]);

        $second->assertSessionHas('success', fn ($m) => str_contains($m, 'Aggiunte 0') && str_contains($m, 'Saltate 2'));
        $this->assertSame(2, Question::where('quiz_id', $target->id)->count());

        // e la pagina le segnala come gia presenti
        $this->actingAs($admin)->get(route('admin.quizzes.questions.picker', $target->id))->assertSee('Già nel quiz');
    }

    public function test_copia_fisicamente_i_media_cosi_eliminare_una_copia_non_rompe_l_altra(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('questions/originale.png', 'immagine');

        $admin = $this->createAdmin();
        $target = $this->createAssignedQuiz();
        $training = $this->createTrainingQuiz(1);
        $source = $training->questions()->first();
        $source->update(['image_path' => 'questions/originale.png']);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.picker.store', $target->id), ['question_ids' => [$source->id]])
            ->assertRedirect();

        $copy = Question::where('quiz_id', $target->id)->firstOrFail();
        $this->assertNotNull($copy->image_path);
        $this->assertNotSame('questions/originale.png', $copy->image_path);
        Storage::disk('public')->assertExists($copy->image_path);

        $this->actingAs($admin)
            ->delete(route('admin.quizzes.questions.destroy', [$target->id, $copy->id]));

        Storage::disk('public')->assertExists('questions/originale.png');
        Storage::disk('public')->assertMissing($copy->image_path);
    }

    public function test_non_aggiunge_domande_a_un_midalario_in_corso(): void
    {
        $admin = $this->createAdmin();
        $live = $this->midalario('running');
        $training = $this->createTrainingQuiz(1);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.picker.store', $live->id), ['question_ids' => $training->questions()->pluck('id')->all()])
            ->assertSessionHasErrors('question_ids');

        $this->assertSame(0, $live->questions()->count());

        $open = $this->midalario('open');
        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.picker.store', $open->id), ['question_ids' => $training->questions()->pluck('id')->all()])
            ->assertSessionHas('success');
        $this->assertSame(1, $open->questions()->count());
    }

    public function test_richiede_almeno_una_domanda_e_ignora_id_non_training(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createAssignedQuiz(['created_by' => $admin->id]);
        $altroOneShot = $this->createAssignedQuiz(['created_by' => $admin->id]);
        $this->addQuestions($altroOneShot, 1);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.picker.store', $target->id), [])
            ->assertSessionHasErrors('question_ids');

        // un id di un One Shot (non Training) non viene copiato
        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.picker.store', $target->id), ['question_ids' => [$altroOneShot->questions()->first()->id]])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Aggiunte 0'));
        $this->assertSame(0, $target->questions()->count());
    }
}
