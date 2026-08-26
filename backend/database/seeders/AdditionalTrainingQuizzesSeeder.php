<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\TrainingCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdditionalTrainingQuizzesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', true)->first();

        if (! $admin) {
            throw new RuntimeException('Serve almeno un amministratore per creare i training quiz.');
        }

        foreach ($this->quizzes() as $quizData) {
            DB::transaction(function () use ($admin, $quizData) {
                $category = TrainingCategory::updateOrCreate(
                    ['slug' => $quizData['category']['slug']],
                    [
                        'name' => $quizData['category']['name'],
                        'description' => $quizData['category']['description'],
                        'is_active' => true,
                    ]
                );

                $quiz = Quiz::updateOrCreate(
                    [
                        'title' => $quizData['title'],
                        'type' => 'training',
                    ],
                    [
                        'description' => $quizData['description'],
                        'training_category_id' => $category->id,
                        'training_question_mode' => '5',
                        'created_by' => $admin->id,
                        'is_active' => true,
                        'leaderboard_visible' => true,
                    ]
                );

                $quiz->trainingAttempts()->delete();
                $quiz->questions()->delete();

                foreach ($quizData['questions'] as $index => $questionData) {
                    $question = Question::create([
                        'quiz_id' => $quiz->id,
                        'question_text' => $questionData['question'],
                        'time_limit_seconds' => 20,
                    ]);

                    foreach ($questionData['answers'] as $answerText) {
                        Answer::create([
                            'question_id' => $question->id,
                            'answer_text' => $answerText,
                            'is_correct' => $answerText === $questionData['correct'],
                        ]);
                    }
                }
            });
        }
    }

    private function quizzes(): array
    {
        return [
            [
                'category' => [
                    'name' => 'Geografia',
                    'slug' => 'geografia',
                    'description' => 'Allenati su capitali, territori e luoghi del mondo.',
                ],
                'title' => 'Training Geografia - Giro del mondo',
                'description' => 'Dieci domande di geografia generale con sessioni casuali da cinque.',
                'questions' => [
                    ['question' => 'Qual è la capitale del Portogallo?', 'correct' => 'Lisbona', 'answers' => ['Lisbona', 'Porto', 'Madrid', 'Siviglia']],
                    ['question' => 'Quale fiume attraversa Parigi?', 'correct' => 'Senna', 'answers' => ['Senna', 'Tamigi', 'Danubio', 'Reno']],
                    ['question' => 'In quale continente si trova il Kenya?', 'correct' => 'Africa', 'answers' => ['Africa', 'Asia', 'Europa', 'Sud America']],
                    ['question' => 'Qual è la montagna più alta del mondo?', 'correct' => 'Everest', 'answers' => ['Everest', 'K2', 'Monte Bianco', 'Kilimangiaro']],
                    ['question' => 'Quale oceano separa Europa e America?', 'correct' => 'Oceano Atlantico', 'answers' => ['Oceano Atlantico', 'Oceano Pacifico', 'Oceano Indiano', 'Oceano Artico']],
                    ['question' => 'Qual è la capitale del Giappone?', 'correct' => 'Tokyo', 'answers' => ['Tokyo', 'Kyoto', 'Osaka', 'Seul']],
                    ['question' => 'Quale paese ha la forma geografica di uno stivale?', 'correct' => 'Italia', 'answers' => ['Italia', 'Grecia', 'Croazia', 'Spagna']],
                    ['question' => 'In quale paese si trova la città di Marrakech?', 'correct' => 'Marocco', 'answers' => ['Marocco', 'Egitto', 'Tunisia', 'Algeria']],
                    ['question' => 'Qual è il deserto caldo più esteso del mondo?', 'correct' => 'Sahara', 'answers' => ['Sahara', 'Gobi', 'Kalahari', 'Atacama']],
                    ['question' => 'Quale mare bagna Venezia?', 'correct' => 'Mare Adriatico', 'answers' => ['Mare Adriatico', 'Mar Tirreno', 'Mar Ionio', 'Mar Ligure']],
                ],
            ],
            [
                'category' => [
                    'name' => 'Scienza',
                    'slug' => 'scienza',
                    'description' => 'Domande rapide su natura, spazio, fisica e biologia.',
                ],
                'title' => 'Training Scienza - Curiosità scientifiche',
                'description' => 'Dieci domande scientifiche con sessioni casuali da cinque.',
                'questions' => [
                    ['question' => 'Quale pianeta è conosciuto come il Pianeta Rosso?', 'correct' => 'Marte', 'answers' => ['Marte', 'Venere', 'Giove', 'Mercurio']],
                    ['question' => 'Qual è la formula chimica dell’acqua?', 'correct' => 'H2O', 'answers' => ['H2O', 'CO2', 'O2', 'NaCl']],
                    ['question' => 'Quale organo pompa il sangue nel corpo umano?', 'correct' => 'Cuore', 'answers' => ['Cuore', 'Fegato', 'Polmone', 'Rene']],
                    ['question' => 'Quale gas assorbono principalmente le piante?', 'correct' => 'Anidride carbonica', 'answers' => ['Anidride carbonica', 'Ossigeno', 'Azoto', 'Idrogeno']],
                    ['question' => 'Quante zampe ha normalmente un insetto?', 'correct' => 'Sei', 'answers' => ['Sei', 'Quattro', 'Otto', 'Dieci']],
                    ['question' => 'Qual è la stella più vicina alla Terra?', 'correct' => 'Sole', 'answers' => ['Sole', 'Sirio', 'Polare', 'Proxima Centauri']],
                    ['question' => 'A quale temperatura congela l’acqua a pressione atmosferica?', 'correct' => '0 °C', 'answers' => ['0 °C', '10 °C', '32 °C', '-100 °C']],
                    ['question' => 'Quale forza ci mantiene sulla superficie terrestre?', 'correct' => 'Gravità', 'answers' => ['Gravità', 'Magnetismo', 'Attrito', 'Pressione']],
                    ['question' => 'Qual è il materiale naturale più duro?', 'correct' => 'Diamante', 'answers' => ['Diamante', 'Ferro', 'Quarzo', 'Granito']],
                    ['question' => 'Quale parte della cellula contiene normalmente il DNA?', 'correct' => 'Nucleo', 'answers' => ['Nucleo', 'Membrana', 'Citoplasma', 'Ribosoma']],
                ],
            ],
            [
                'category' => [
                    'name' => 'Cinema',
                    'slug' => 'cinema',
                    'description' => 'Mettiti alla prova con film, personaggi e registi famosi.',
                ],
                'title' => 'Training Cinema - Grande schermo',
                'description' => 'Dieci domande sul cinema internazionale con sessioni casuali da cinque.',
                'questions' => [
                    ['question' => 'Chi ha diretto Titanic?', 'correct' => 'James Cameron', 'answers' => ['James Cameron', 'Steven Spielberg', 'Christopher Nolan', 'Ridley Scott']],
                    ['question' => 'In quale saga compare il personaggio Darth Vader?', 'correct' => 'Star Wars', 'answers' => ['Star Wars', 'Star Trek', 'Dune', 'Matrix']],
                    ['question' => 'Come si chiama il giovane mago interpretato da Daniel Radcliffe?', 'correct' => 'Harry Potter', 'answers' => ['Harry Potter', 'Frodo Baggins', 'Peter Parker', 'Percy Jackson']],
                    ['question' => 'Quale film racconta la storia di un parco popolato da dinosauri clonati?', 'correct' => 'Jurassic Park', 'answers' => ['Jurassic Park', 'King Kong', 'Godzilla', 'Jumanji']],
                    ['question' => 'Chi interpreta Iron Man nel Marvel Cinematic Universe?', 'correct' => 'Robert Downey Jr.', 'answers' => ['Robert Downey Jr.', 'Chris Evans', 'Chris Hemsworth', 'Mark Ruffalo']],
                    ['question' => 'Quale regista ha diretto Pulp Fiction?', 'correct' => 'Quentin Tarantino', 'answers' => ['Quentin Tarantino', 'Martin Scorsese', 'Francis Ford Coppola', 'David Fincher']],
                    ['question' => 'Qual è il nome del protagonista del film Rocky?', 'correct' => 'Rocky Balboa', 'answers' => ['Rocky Balboa', 'Rambo', 'Apollo Creed', 'Ivan Drago']],
                    ['question' => 'In Matrix, quale pillola sceglie Neo?', 'correct' => 'La pillola rossa', 'answers' => ['La pillola rossa', 'La pillola blu', 'La pillola verde', 'Nessuna pillola']],
                    ['question' => 'Quale film d’animazione ha come protagonista il leone Simba?', 'correct' => 'Il re leone', 'answers' => ['Il re leone', 'Madagascar', 'Zootropolis', 'Il libro della giungla']],
                    ['question' => 'Chi ha composto la colonna sonora de Il buono, il brutto, il cattivo?', 'correct' => 'Ennio Morricone', 'answers' => ['Ennio Morricone', 'Hans Zimmer', 'John Williams', 'Nino Rota']],
                ],
            ],
        ];
    }
}
