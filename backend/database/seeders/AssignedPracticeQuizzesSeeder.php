<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssignedPracticeQuizzesSeeder extends Seeder
{
    private const LOGO_IMAGE = 'questions/O1cg5pdrFNdAZyDU2Dqc8CopT9sxjHP26CrSchQl.png';

    private const BURGER_IMAGE = 'questions/SMusPqCJCF4otIlpUqwYsXpGtp50BMaZEfTI4E1t.png';

    private const SAMPLE_AUDIO = 'questions/RKlPGnL40hF3J5LUFJfMLvf2grkqfuEJ6kYcHkIb.ogg';

    private const SAMPLE_VIDEO = 'questions/EF60LNoUbcBHSzneC20OocJbR26iKYfMp3brLWa8.mp4';

    public function run(): void
    {
        $admin = User::where('is_admin', true)->first();

        if (! $admin) {
            throw new RuntimeException('Serve almeno un amministratore per creare i quiz.');
        }

        foreach ($this->quizzes() as $quizData) {
            DB::transaction(function () use ($admin, $quizData) {
                $quiz = Quiz::updateOrCreate(
                    [
                        'title' => $quizData['title'],
                        'type' => 'assigned',
                    ],
                    [
                        'description' => $quizData['description'],
                        'training_category_id' => null,
                        'training_question_mode' => null,
                        'created_by' => $admin->id,
                        'is_active' => true,
                        'leaderboard_visible' => true,
                    ]
                );

                $quiz->attempts()->delete();
                $quiz->questions()->delete();

                foreach ($quizData['questions'] as $index => $questionData) {
                    $question = Question::create([
                        'quiz_id' => $quiz->id,
                        'question_text' => $questionData['question'],
                        'image_path' => $questionData['image'] ?? null,
                        'audio_path' => $questionData['audio'] ?? null,
                        'video_path' => $questionData['video'] ?? null,
                        'time_limit_seconds' => $questionData['time'] ?? 20,
                        'order' => $index + 1,
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
                'title' => 'Quiz Cultura Generale',
                'description' => 'Una sfida rapida tra storia, geografia, scienza e curiosità.',
                'questions' => [
                    $this->question('Qual è la capitale dell’Australia?', 'Canberra', ['Canberra', 'Sydney', 'Melbourne', 'Perth']),
                    $this->question('Chi dipinse la Gioconda?', 'Leonardo da Vinci', ['Leonardo da Vinci', 'Michelangelo', 'Raffaello', 'Caravaggio']),
                    $this->question('Quale pianeta è il più grande del Sistema Solare?', 'Giove', ['Giove', 'Saturno', 'Terra', 'Marte']),
                    $this->question('In quale anno terminò la Seconda guerra mondiale?', '1945', ['1945', '1939', '1942', '1950']),
                    $this->question('Qual è la lingua ufficiale del Brasile?', 'Portoghese', ['Portoghese', 'Spagnolo', 'Francese', 'Inglese']),
                    $this->question('Quanti lati ha un esagono?', 'Sei', ['Sei', 'Cinque', 'Sette', 'Otto']),
                    $this->question('Quale testo compare nell’immagine?', 'MIDA LOT', ['MIDA LOT', 'QUIZ TIME', 'MIDALOT APP', 'LOT MIDA'], image: self::LOGO_IMAGE),
                    $this->question('Quale metallo ha simbolo chimico Au?', 'Oro', ['Oro', 'Argento', 'Rame', 'Alluminio']),
                ],
            ],
            [
                'title' => 'Quiz Italia',
                'description' => 'Città, storia, arte e tradizioni italiane.',
                'questions' => [
                    $this->question('Qual è il capoluogo della Toscana?', 'Firenze', ['Firenze', 'Pisa', 'Siena', 'Livorno']),
                    $this->question('Quale vulcano domina il paesaggio di Napoli?', 'Vesuvio', ['Vesuvio', 'Etna', 'Stromboli', 'Vulcano']),
                    $this->question('In quale città si trova il Colosseo?', 'Roma', ['Roma', 'Milano', 'Verona', 'Torino']),
                    $this->question('Quale mare bagna la costa occidentale della Sardegna?', 'Mar di Sardegna', ['Mar di Sardegna', 'Mare Adriatico', 'Mar Ionio', 'Mar Nero']),
                    $this->question('Chi ha scritto la Divina Commedia?', 'Dante Alighieri', ['Dante Alighieri', 'Alessandro Manzoni', 'Giovanni Boccaccio', 'Giacomo Leopardi']),
                    $this->question('Qual è il capoluogo della Sicilia?', 'Palermo', ['Palermo', 'Catania', 'Messina', 'Siracusa']),
                    $this->question('Quale ingrediente dell’immagine è un formaggio?', 'La fetta gialla', ['La fetta gialla', 'Il pomodoro', 'Il pane', 'La lattuga'], image: self::BURGER_IMAGE),
                    $this->question('Quale città italiana è famosa per i canali e le gondole?', 'Venezia', ['Venezia', 'Genova', 'Bologna', 'Bari']),
                ],
            ],
            [
                'title' => 'Quiz Tecnologia e Internet',
                'description' => 'Concetti fondamentali di informatica, web e sicurezza digitale.',
                'questions' => [
                    $this->question('Che cosa significa CPU?', 'Central Processing Unit', ['Central Processing Unit', 'Computer Personal Utility', 'Central Program User', 'Core Processing Upload']),
                    $this->question('Quale linguaggio viene eseguito principalmente nel browser?', 'JavaScript', ['JavaScript', 'PHP', 'SQL', 'Bash']),
                    $this->question('Che cosa indica HTTPS?', 'Una connessione web cifrata', ['Una connessione web cifrata', 'Un file immagine', 'Un linguaggio di programmazione', 'Una memoria del computer']),
                    $this->question('Quale dispositivo collega una rete locale a Internet?', 'Router', ['Router', 'Monitor', 'Stampante', 'Tastiera']),
                    $this->question('Quale estensione identifica normalmente un’immagine PNG?', '.png', ['.png', '.mp3', '.php', '.zip']),
                    $this->question('Che cosa aiuta a proteggere un account oltre alla password?', 'Autenticazione a due fattori', ['Autenticazione a due fattori', 'Luminosità automatica', 'Modalità aereo', 'Cronologia del browser']),
                    $this->question('Il contenuto mostrato è un esempio di quale tipo di file?', 'Immagine', ['Immagine', 'Audio', 'Video', 'Documento di testo'], image: self::LOGO_IMAGE),
                    $this->question('Quale unità è maggiore?', 'Gigabyte', ['Gigabyte', 'Megabyte', 'Kilobyte', 'Byte']),
                ],
            ],
            [
                'title' => 'Quiz Logica e Calcolo',
                'description' => 'Sequenze, problemi rapidi e ragionamento numerico.',
                'questions' => [
                    $this->question('Quale numero completa la sequenza 2, 4, 6, 8, ...?', '10', ['10', '9', '11', '12']),
                    $this->question('Quanto fa 15 + 27?', '42', ['42', '32', '41', '52']),
                    $this->question('Se tutti i gatti sono animali, quale affermazione è certamente vera?', 'Ogni gatto è un animale', ['Ogni gatto è un animale', 'Ogni animale è un gatto', 'Nessun gatto è un animale', 'Tutti gli animali miagolano']),
                    $this->question('Quale numero è la metà di 150?', '75', ['75', '50', '100', '125']),
                    $this->question('Quanto vale 8 × 7?', '56', ['56', '54', '64', '48']),
                    $this->question('Quale elemento è diverso dagli altri?', 'Triangolo', ['Triangolo', 'Rosso', 'Blu', 'Verde']),
                    $this->question('Quanti strati principali sono visibili nell’hamburger?', 'Cinque', ['Cinque', 'Tre', 'Sette', 'Nove'], image: self::BURGER_IMAGE),
                    $this->question('Un’ora e mezza corrisponde a quanti minuti?', '90', ['90', '60', '75', '120']),
                ],
            ],
            [
                'title' => 'Quiz Sfida Multimediale',
                'description' => 'Domande miste che verificano immagini, audio, video e osservazione.',
                'questions' => [
                    $this->question('Qual è il colore dominante dello sfondo del logo?', 'Giallo', ['Giallo', 'Rosso', 'Verde', 'Viola'], image: self::LOGO_IMAGE),
                    $this->question('Quale cibo è rappresentato nell’immagine?', 'Hamburger', ['Hamburger', 'Pizza', 'Gelato', 'Insalata'], image: self::BURGER_IMAGE),
                    $this->question('Riproduci l’audio di prova e seleziona la risposta numero 2.', '2', ['1', '2', '3', '4'], audio: self::SAMPLE_AUDIO, time: 30),
                    $this->question('Riproduci il video di prova e seleziona la risposta numero 3.', '3', ['1', '2', '3', '4'], video: self::SAMPLE_VIDEO, time: 45),
                    $this->question('Nel logo, quale oggetto appare sopra la mano?', 'Una moneta', ['Una moneta', 'Un libro', 'Una stella', 'Un dado'], image: self::LOGO_IMAGE),
                    $this->question('Quale ingrediente verde è visibile nell’immagine?', 'Lattuga', ['Lattuga', 'Basilico', 'Zucchina', 'Spinaci'], image: self::BURGER_IMAGE),
                    $this->question('Quale controllo permette normalmente di avviare audio e video?', 'Play', ['Play', 'Salva', 'Stampa', 'Ricarica']),
                    $this->question('Quale formato è comunemente utilizzato per i video sul web?', 'MP4', ['MP4', 'TXT', 'CSV', 'DOCX']),
                ],
            ],
        ];
    }

    private function question(
        string $question,
        string $correct,
        array $answers,
        ?string $image = null,
        ?string $audio = null,
        ?string $video = null,
        int $time = 20
    ): array {
        return compact('question', 'correct', 'answers', 'image', 'audio', 'video', 'time');
    }
}
