<?php

use App\Http\Controllers\Admin\AvatarController as AdminAvatarController;
use App\Http\Controllers\Admin\ChallengeController as AdminChallengeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventRequestController;
use App\Http\Controllers\Admin\ITunesController;
use App\Http\Controllers\Admin\MidalarioController;
use App\Http\Controllers\Admin\MinigiocoController;
use App\Http\Controllers\Admin\MinigiocoRoundController;
use App\Http\Controllers\Admin\PeriodLeaderboardController;
use App\Http\Controllers\Admin\PrizeAwardController;
use App\Http\Controllers\Admin\ProspectController;
use App\Http\Controllers\Admin\ProspectSearchController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuestionDraftController;
use App\Http\Controllers\Admin\QuestionPickerController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\ShowcaseImageController;
use App\Http\Controllers\Admin\TrainingCategoryController;
use App\Http\Controllers\Admin\TrainingQuestionReportController;
use App\Http\Controllers\Admin\TrainingSubcategoryController;
use App\Http\Controllers\Admin\TrainingQuizController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if (Auth::check() && Auth::user()->is_admin) {
        return redirect()->route('admin.index');
    }

    if (Auth::check()) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.index');
})->middleware(['auth', 'verified', 'admin'])->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])
    ->name('admin.')
    ->prefix('admin')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])
            ->name('index');

        Route::get('users', [UserController::class, 'index'])
            ->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])
            ->name('users.show');
        Route::patch('users/{user}/nickname', [UserController::class, 'updateNickname'])
            ->name('users.nickname.update');

        Route::get('training/categories', [TrainingCategoryController::class, 'index'])
            ->name('training.categories.index');
        Route::post('training/categories', [TrainingCategoryController::class, 'store'])
            ->name('training.categories.store');
        Route::put('training/categories/{category}', [TrainingCategoryController::class, 'update'])
            ->name('training.categories.update');
        Route::delete('training/categories/{category}', [TrainingCategoryController::class, 'destroy'])
            ->name('training.categories.destroy');

        Route::post('training/categories/{category}/subcategories', [TrainingSubcategoryController::class, 'store'])
            ->name('training.subcategories.store');
        Route::put('training/subcategories/{subcategory}', [TrainingSubcategoryController::class, 'update'])
            ->name('training.subcategories.update');
        Route::delete('training/subcategories/{subcategory}', [TrainingSubcategoryController::class, 'destroy'])
            ->name('training.subcategories.destroy');

        Route::resource('training/quizzes', TrainingQuizController::class)
            ->names('training.quizzes')
            ->parameters(['quizzes' => 'quiz']);

        Route::get('training/quizzes/{quiz}/leaderboard', [TrainingQuizController::class, 'leaderboard'])
            ->name('training.quizzes.leaderboard');
        Route::patch('training/quizzes/{quiz}/toggle-leaderboard', [TrainingQuizController::class, 'toggleLeaderboard'])
            ->name('training.quizzes.toggleLeaderboard');

        Route::get('midalario', [MidalarioController::class, 'index'])
            ->name('midalario.index');
        Route::get('midalario/create', [MidalarioController::class, 'create'])
            ->name('midalario.create');
        Route::post('midalario', [MidalarioController::class, 'store'])
            ->name('midalario.store');
        Route::get('midalario/{quiz}/edit', [MidalarioController::class, 'edit'])
            ->name('midalario.edit');
        Route::put('midalario/{quiz}', [MidalarioController::class, 'update'])
            ->name('midalario.update');
        Route::delete('midalario/{quiz}', [MidalarioController::class, 'destroy'])
            ->name('midalario.destroy');
        Route::get('midalario/{quiz}/monitor', [MidalarioController::class, 'monitor'])
            ->name('midalario.monitor');
        Route::get('midalario/{quiz}/participants/{user}/answers', [MidalarioController::class, 'participantAnswers'])
            ->name('midalario.participants.answers');
        Route::get('midalario/{quiz}/pdf-data', [MidalarioController::class, 'pdfData'])
            ->name('midalario.pdf-data');
        Route::patch('midalario/{quiz}/close', [MidalarioController::class, 'closeParticipation'])
            ->name('midalario.close');
        Route::patch('midalario/{quiz}/reopen', [MidalarioController::class, 'reopenParticipation'])
            ->name('midalario.reopen');
        Route::patch('midalario/{quiz}/start', [MidalarioController::class, 'start'])
            ->name('midalario.start');

        Route::get('itunes/search', [ITunesController::class, 'search'])
            ->name('itunes.search');

        Route::get('showcase', [ShowcaseImageController::class, 'index'])
            ->name('showcase.index');
        Route::post('showcase', [ShowcaseImageController::class, 'store'])
            ->name('showcase.store');
        Route::delete('showcase/{showcase}', [ShowcaseImageController::class, 'destroy'])
            ->name('showcase.destroy');

        Route::get('reports', [TrainingQuestionReportController::class, 'index'])
            ->name('reports.index');
        Route::patch('reports/{report}', [TrainingQuestionReportController::class, 'update'])
            ->name('reports.update');
        Route::delete('reports/{report}', [TrainingQuestionReportController::class, 'destroy'])
            ->name('reports.destroy');

        Route::get('avatar', [AdminAvatarController::class, 'index'])
            ->name('avatar.index');
        Route::get('avatar/items/{item}/edit', [AdminAvatarController::class, 'edit'])
            ->name('avatar.edit');
        Route::put('avatar/items/{item}', [AdminAvatarController::class, 'update'])
            ->name('avatar.update');
        Route::get('avatar/vetrina', [AdminAvatarController::class, 'showcase'])
            ->name('avatar.showcase');
        Route::post('avatar/vetrina', [AdminAvatarController::class, 'saveShowcase'])
            ->name('avatar.showcase.save');
        Route::put('avatar/vetrina/impostazioni', [AdminAvatarController::class, 'saveSettings'])
            ->name('avatar.settings.save');
        Route::post('avatar/temi/{theme}/vendita', [AdminAvatarController::class, 'themeSale'])
            ->name('avatar.theme.sale');
        Route::post('avatar/promozioni', [AdminAvatarController::class, 'storePromotion'])
            ->name('avatar.promo.store');
        Route::post('avatar/promozioni/{promotion}/toggle', [AdminAvatarController::class, 'togglePromotion'])
            ->name('avatar.promo.toggle');
        Route::delete('avatar/promozioni/{promotion}', [AdminAvatarController::class, 'deletePromotion'])
            ->name('avatar.promo.delete');
        Route::post('avatar/gift', [AdminAvatarController::class, 'gift'])
            ->name('avatar.gift');
        Route::get('avatar/users/{user}', [AdminAvatarController::class, 'user'])
            ->name('avatar.user');
        Route::delete('avatar/users/{user}/items/{item}', [AdminAvatarController::class, 'revoke'])
            ->name('avatar.revoke');

        Route::get('challenges', [AdminChallengeController::class, 'index'])
            ->name('challenges.index');
        Route::get('challenges/{challenge}', [AdminChallengeController::class, 'show'])
            ->name('challenges.show');

        Route::get('event-requests', [EventRequestController::class, 'index'])
            ->name('event-requests.index');
        Route::patch('event-requests/{eventRequest}', [EventRequestController::class, 'update'])
            ->name('event-requests.update');
        Route::delete('event-requests/{eventRequest}', [EventRequestController::class, 'destroy'])
            ->name('event-requests.destroy');

        // Clienti da contattare (profili Instagram di attivita' + operazioni di ricerca)
        Route::get('clienti/ricerche', [ProspectSearchController::class, 'index'])->name('prospect-searches.index');
        Route::post('clienti/ricerche', [ProspectSearchController::class, 'store'])->name('prospect-searches.store');
        Route::patch('clienti/ricerche/{search}', [ProspectSearchController::class, 'update'])->name('prospect-searches.update');
        Route::delete('clienti/ricerche/{search}', [ProspectSearchController::class, 'destroy'])->name('prospect-searches.destroy');
        Route::get('clienti', [ProspectController::class, 'index'])->name('prospects.index');
        Route::get('clienti/nuovo', [ProspectController::class, 'create'])->name('prospects.create');
        Route::get('clienti/esporta', [ProspectController::class, 'export'])->name('prospects.export');
        Route::post('clienti', [ProspectController::class, 'store'])->name('prospects.store');
        Route::get('clienti/{prospect}/modifica', [ProspectController::class, 'edit'])->name('prospects.edit');
        Route::put('clienti/{prospect}', [ProspectController::class, 'update'])->name('prospects.update');
        Route::patch('clienti/{prospect}/stato', [ProspectController::class, 'status'])->name('prospects.status');
        Route::delete('clienti/{prospect}', [ProspectController::class, 'destroy'])->name('prospects.destroy');

        // CRUD Quiz
        Route::resource('quizzes', QuizController::class);

        // CRUD Domande
        Route::get('quizzes/{quiz}/pesca-domande', [QuestionPickerController::class, 'index'])
            ->name('quizzes.questions.picker');
        Route::post('quizzes/{quiz}/pesca-domande', [QuestionPickerController::class, 'store'])
            ->name('quizzes.questions.picker.store');
        Route::get('domande-da-confermare',[QuestionDraftController::class, 'index'])
            ->name('question-drafts.index');
        Route::post('quizzes/{quiz}/questions/approve-all',[QuestionController::class, 'approveAll'])
            ->name('quizzes.questions.approve-all');
        Route::post('quizzes/{quiz}/questions/{question}/approve', [QuestionController::class, 'approve'])
            ->name('quizzes.questions.approve');
        Route::resource('quizzes.questions', QuestionController::class);

        /*
        |--------------------------------------------------------------------------
        | GESTIONE UTENTI QUIZ
        |--------------------------------------------------------------------------
        */

        // Pagina gestione utenti del quiz
        Route::get(
            'quizzes/{quiz}/users',
            [QuizController::class, 'manageUsers']
        )->name('quizzes.users');

        // Ricerca utenti (AJAX) legata al quiz
        Route::get(
            'quizzes/{quiz}/users/search',
            [QuizController::class, 'searchUsers']
        )->name('quizzes.users.search');

        // Associa utenti al quiz
        Route::post(
            'quizzes/{quiz}/attach-users',
            [QuizController::class, 'attachUsers']
        )->name('quizzes.attachUsers');

        // Classifica utenti
        Route::get('quizzes/{quiz}/leaderboard', [QuizController::class, 'leaderboard'])
            ->name('quizzes.leaderboard');

        Route::patch('quizzes/{quiz}/toggle-leaderboard', [QuizController::class, 'toggleLeaderboard'])
            ->name('quizzes.toggleLeaderboard');

        // Rimuovi utente dal quiz
        Route::delete(
            'quizzes/{quiz}/users/{user}',
            [QuizController::class, 'detachUser']
        )->name('quizzes.detachUser');

        /*
        |--------------------------------------------------------------------------
        | MINIGIOCHI
        |--------------------------------------------------------------------------
        */

        // CRUD Minigiochi
        Route::resource('minigiochi', MinigiocoController::class)
            ->parameters(['minigiochi' => 'minigioco']);

        // CRUD Domande (round) dei minigiochi
        Route::resource('minigiochi.rounds', MinigiocoRoundController::class)
            ->parameters(['minigiochi' => 'minigioco', 'rounds' => 'round']);

        Route::get('minigiochi/{minigioco}/leaderboard', [MinigiocoController::class, 'leaderboard'])
            ->name('minigiochi.leaderboard');

        Route::patch('minigiochi/{minigioco}/toggle-leaderboard', [MinigiocoController::class, 'toggleLeaderboard'])
            ->name('minigiochi.toggleLeaderboard');

        Route::get('classifica-premi', [PeriodLeaderboardController::class, 'index'])
            ->name('period-leaderboard.index');

        Route::get('premi-monete', [PrizeAwardController::class, 'index'])
            ->name('prize-awards.index');
    });

Route::get('/cities/search', [CityController::class, 'search'])
    ->middleware('throttle:60,1')
    ->name('cities.search');

require __DIR__.'/auth.php';
