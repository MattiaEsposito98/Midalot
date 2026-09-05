<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\TrainingCategory;
use App\Models\TrainingSubcategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SubcategoryExampleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', true)->first();

        if (! $admin) {
            throw new RuntimeException('Serve almeno un amministratore per creare i training quiz.');
        }

        foreach ($this->categories() as $categoryData) {
            DB::transaction(function () use ($admin, $categoryData) {
                $category = TrainingCategory::updateOrCreate(
                    ['slug' => $categoryData['slug']],
                    [
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'],
                        'is_active' => true,
                    ]
                );

                foreach ($categoryData['subcategories'] as $subcategoryData) {
                    $subcategory = TrainingSubcategory::updateOrCreate(
                        [
                            'training_category_id' => $category->id,
                            'slug' => Str::slug($subcategoryData['name']),
                        ],
                        [
                            'name' => $subcategoryData['name'],
                            'is_active' => true,
                        ]
                    );

                    $quiz = Quiz::updateOrCreate(
                        [
                            'title' => $subcategoryData['quiz_title'],
                            'type' => 'training',
                        ],
                        [
                            'description' => $subcategoryData['quiz_description'],
                            'training_category_id' => $category->id,
                            'training_subcategory_id' => $subcategory->id,
                            'training_question_mode' => '5',
                            'created_by' => $admin->id,
                            'is_active' => true,
                            'leaderboard_visible' => true,
                        ]
                    );

                    $quiz->trainingAttempts()->delete();
                    $quiz->questions()->delete();

                    foreach ($subcategoryData['questions'] as $questionData) {
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
                }
            });
        }
    }

    private function categories(): array
    {
        return [
            [
                'name' => 'Disney',
                'slug' => 'disney',
                'description' => 'Il mondo Disney in tre grandi universi: Disney classico, Marvel e Star Wars.',
                'subcategories' => [
                    [
                        'name' => 'Disney',
                        'quiz_title' => 'Training Disney - Classici animati',
                        'quiz_description' => 'Domande sui classici d\'animazione Disney.',
                        'questions' => [
                            ['question' => 'Chi è la protagonista de "La Sirenetta"?', 'correct' => 'Ariel', 'answers' => ['Ariel', 'Belle', 'Jasmine', 'Aurora']],
                            ['question' => 'In quale film Disney compare il personaggio Olaf?', 'correct' => 'Frozen', 'answers' => ['Frozen', 'Coco', 'Encanto', 'Up']],
                            ['question' => 'Come si chiama l\'elefantino Disney dalle grandi orecchie?', 'correct' => 'Dumbo', 'answers' => ['Dumbo', 'Baloo', 'Winnie', 'Bambi']],
                            ['question' => 'Chi pronuncia la celebre frase "Hakuna Matata"?', 'correct' => 'Timon e Pumbaa', 'answers' => ['Timon e Pumbaa', 'Simba e Nala', 'Mufasa', 'Scar']],
                            ['question' => 'Qual è il nome del castello simbolo Disney?', 'correct' => 'Castello della Bella Addormentata', 'answers' => ['Castello della Bella Addormentata', 'Castello di Cenerentola', 'Castello di Hogwarts', 'Castello di Neuschwanstein']],
                        ],
                    ],
                    [
                        'name' => 'Marvel',
                        'quiz_title' => 'Training Disney - Marvel',
                        'quiz_description' => 'Domande sui supereroi Marvel.',
                        'questions' => [
                            ['question' => 'Qual è il vero nome di Iron Man?', 'correct' => 'Tony Stark', 'answers' => ['Tony Stark', 'Steve Rogers', 'Bruce Banner', 'Peter Parker']],
                            ['question' => 'Di che materiale è fatto lo scudo di Captain America?', 'correct' => 'Vibranio', 'answers' => ['Vibranio', 'Adamantio', 'Titanio', 'Acciaio']],
                            ['question' => 'Chi è il dio del tuono nell\'universo Marvel?', 'correct' => 'Thor', 'answers' => ['Thor', 'Loki', 'Odino', 'Hercules']],
                            ['question' => 'Qual è il nome della squadra di supereroi principale Marvel?', 'correct' => 'Avengers', 'answers' => ['Avengers', 'Justice League', 'X-Men', 'Guardiani della Galassia']],
                            ['question' => 'Chi interpreta Spider-Man nel MCU (dal 2016)?', 'correct' => 'Tom Holland', 'answers' => ['Tom Holland', 'Tobey Maguire', 'Andrew Garfield', 'Andrew Lincoln']],
                        ],
                    ],
                    [
                        'name' => 'Star Wars',
                        'quiz_title' => 'Training Disney - Star Wars',
                        'quiz_description' => 'Domande sull\'universo di Star Wars.',
                        'questions' => [
                            ['question' => 'Chi è il padre di Luke Skywalker?', 'correct' => 'Darth Vader', 'answers' => ['Darth Vader', 'Obi-Wan Kenobi', 'Yoda', 'Han Solo']],
                            ['question' => 'Come si chiama la nave di Han Solo?', 'correct' => 'Millennium Falcon', 'answers' => ['Millennium Falcon', 'Star Destroyer', 'X-Wing', 'Slave I']],
                            ['question' => 'Qual è il colore della spada laser di un Jedi tradizionale?', 'correct' => 'Blu o verde', 'answers' => ['Blu o verde', 'Rosso', 'Viola', 'Nero']],
                            ['question' => 'Chi pronuncia la frase "Che la Forza sia con te"?', 'correct' => 'Personaggi Jedi', 'answers' => ['Personaggi Jedi', 'Solo i Sith', 'Solo i droidi', 'Nessuno']],
                            ['question' => 'Come si chiama il piccolo droide arancione e bianco di "Il risveglio della Forza"?', 'correct' => 'BB-8', 'answers' => ['BB-8', 'R2-D2', 'C-3PO', 'K-2SO']],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Matematica e Logica',
                'slug' => 'matematica-e-logica',
                'description' => 'Allenati con calcoli, sequenze e rompicapo logici.',
                'subcategories' => [
                    [
                        'name' => 'Matematica e Logica',
                        'quiz_title' => 'Training Matematica e Logica - Base',
                        'quiz_description' => 'Domande di matematica e logica generale.',
                        'questions' => [
                            ['question' => 'Quanto fa 12 x 8?', 'correct' => '96', 'answers' => ['96', '86', '108', '112']],
                            ['question' => 'Qual è il numero successivo nella sequenza 2, 4, 8, 16, ...?', 'correct' => '32', 'answers' => ['32', '24', '30', '18']],
                            ['question' => 'Quanto fa la radice quadrata di 81?', 'correct' => '9', 'answers' => ['9', '8', '7', '11']],
                            ['question' => 'Se un treno viaggia a 60 km/h, quanti km percorre in 30 minuti?', 'correct' => '30 km', 'answers' => ['30 km', '60 km', '15 km', '45 km']],
                            ['question' => 'Quanti lati ha un esagono?', 'correct' => '6', 'answers' => ['6', '5', '7', '8']],
                        ],
                    ],
                ],
            ],
        ];
    }
}
