<?php

namespace Tests\Feature;

use App\Models\CoinEvent;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\MidalarioFinalizer;
use App\Services\PrizeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Monete ai primi 5 della classifica mensile e di ogni Midalario
 * (scale in config/prizes.php), con avviso all'utente.
 */
class PrizeCoinsTest extends TestCase
{
    use RefreshDatabase;

    private function utente(): User
    {
        return User::factory()->create(['is_admin' => false]);
    }

    private function puntiMesePassato(User $u, int $score): void
    {
        $quando = now()->subMonthNoOverflow()->startOfMonth()->addDays(3);

        DB::table('daily_login_bonuses')->insert([
            'user_id' => $u->id,
            'bonus_date' => $quando->toDateString(),
            'score' => $score,
            'created_at' => $quando,
            'updated_at' => $quando,
        ]);
    }

    public function test_la_mensile_premia_i_primi_cinque_con_la_scala_configurata(): void
    {
        $utenti = [];
        foreach ([9000, 8000, 7000, 6000, 5000, 4000] as $score) {
            $u = $this->utente();
            $this->puntiMesePassato($u, $score);
            $utenti[$score] = $u;
        }

        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);

        $attese = [9000 => 1000, 8000 => 600, 7000 => 400, 6000 => 250, 5000 => 150, 4000 => 0];
        foreach ($attese as $score => $monete) {
            $this->assertSame($monete, (int) $utenti[$score]->fresh()->coins, "Posto con {$score} punti");
        }

        $this->assertSame(5, DB::table('prize_awards')->where('kind', 'monthly')->count());
    }

    public function test_pari_merito_stessa_posizione_e_si_salta_la_successiva(): void
    {
        $a = $this->utente();
        $b = $this->utente();
        $c = $this->utente();
        $this->puntiMesePassato($a, 9000);
        $this->puntiMesePassato($b, 9000);
        $this->puntiMesePassato($c, 5000);

        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);

        $this->assertSame(1000, (int) $a->fresh()->coins);
        $this->assertSame(1000, (int) $b->fresh()->coins);
        $this->assertSame(400, (int) $c->fresh()->coins, 'Due primi: il successivo e\' 3°.');
    }

    public function test_rieseguire_la_mensile_non_raddoppia_le_monete(): void
    {
        $a = $this->utente();
        $this->puntiMesePassato($a, 9000);

        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);
        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);

        $this->assertSame(1000, (int) $a->fresh()->coins);
        $this->assertSame(1, CoinEvent::where('user_id', $a->id)->where('source', 'monthly_prize')->count());
        $this->assertSame(1, DB::table('prize_awards')->where('user_id', $a->id)->count());
    }

    public function test_zero_punti_non_ricevono_nulla(): void
    {
        $a = $this->utente();
        $this->puntiMesePassato($a, 0);

        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);

        $this->assertSame(0, (int) $a->fresh()->coins);
        $this->assertSame(0, DB::table('prize_awards')->count());
    }

    private function midalarioChiuso(array $risultati): Quiz
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $quiz = Quiz::create([
            'title' => 'Midalario di prova',
            'type' => 'midalario',
            'created_by' => $admin->id,
            'is_active' => true,
            'leaderboard_visible' => true,
            'midalario_status' => 'running',
            'midalario_started_at' => now()->subMinutes(10),
        ]);

        foreach ($risultati as [$score, $tempo]) {
            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $this->utente()->id,
                'started_at' => now()->subMinutes(10),
                'completed' => true,
                'finished_at' => now()->subMinutes(9),
                'score' => $score,
                'total_time' => $tempo,
                'question_order' => [],
            ]);
        }

        return $quiz;
    }

    private function monete(Quiz $quiz, int $score, int $tempo): int
    {
        $a = QuizAttempt::where('quiz_id', $quiz->id)->where('score', $score)->where('total_time', $tempo)->first();

        return (int) $a->user->fresh()->coins;
    }

    public function test_il_midalario_premia_i_primi_cinque_in_automatico_alla_chiusura(): void
    {
        $quiz = $this->midalarioChiuso([
            [9000, 1000], [9000, 2000], [8000, 1000], [7000, 1000], [6000, 1000], [5000, 1000], [0, 1000],
        ]);

        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);

        // Punteggio uguale ma tempo diverso: posizioni diverse (1° e 2°).
        $this->assertSame(500, $this->monete($quiz, 9000, 1000));
        $this->assertSame(300, $this->monete($quiz, 9000, 2000));
        $this->assertSame(200, $this->monete($quiz, 8000, 1000));
        $this->assertSame(120, $this->monete($quiz, 7000, 1000));
        $this->assertSame(80, $this->monete($quiz, 6000, 1000));
        $this->assertSame(0, $this->monete($quiz, 5000, 1000), 'Il 6° non riceve nulla.');
        $this->assertSame(0, $this->monete($quiz, 0, 1000));
    }

    public function test_midalario_stesso_punteggio_e_stesso_tempo_condividono_la_posizione(): void
    {
        $quiz = $this->midalarioChiuso([[9000, 1000], [9000, 1000], [4000, 1000]]);

        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);

        $this->assertSame(500, $this->monete($quiz, 9000, 1000));
        $this->assertSame(200, $this->monete($quiz, 4000, 1000), 'Due primi: il successivo e\' 3°.');
        $this->assertSame(2, DB::table('prize_awards')->where('position', 1)->count());
    }

    public function test_riassegnare_il_midalario_non_raddoppia(): void
    {
        $quiz = $this->midalarioChiuso([[9000, 1000]]);

        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);
        (new MidalarioFinalizer())->assignCoinPrizes($quiz);

        $this->assertSame(500, $this->monete($quiz, 9000, 1000));
        $this->assertSame(1, DB::table('prize_awards')->count());
    }

    public function test_lutente_vede_lavviso_una_sola_volta(): void
    {
        $quiz = $this->midalarioChiuso([[9000, 1000]]);
        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);
        $utente = QuizAttempt::where('quiz_id', $quiz->id)->first()->user;

        Sanctum::actingAs($utente);

        $news = $this->getJson('/api/avatar/news')->assertOk()->json('prizes');
        $this->assertCount(1, $news);
        $this->assertSame(500, $news[0]['coins']);
        $this->assertSame(1, $news[0]['position']);
        $this->assertSame('Midalario: Midalario di prova', $news[0]['label']);

        $this->postJson('/api/avatar/news/seen')->assertOk();
        $this->assertSame([], $this->getJson('/api/avatar/news')->json('prizes'));
    }

    public function test_classificazione_con_pari_merito(): void
    {
        $ranked = PrizeService::rank([
            ['user_id' => 1, 'key' => [10]],
            ['user_id' => 2, 'key' => [10]],
            ['user_id' => 3, 'key' => [5]],
        ]);

        $this->assertSame([1, 1, 3], array_column($ranked, 'position'));
        $this->assertSame(0, PrizeService::coinsFor('monthly', 6));
        $this->assertSame(0, PrizeService::coinsFor('monthly', 0) ?: 0);
    }
}
