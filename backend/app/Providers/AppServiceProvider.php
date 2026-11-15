<?php

namespace App\Providers;

use App\Models\EventRequest;
use App\Models\Question;
use App\Models\TrainingQuestionReport;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.admin', function ($view) {
            $view->with(
                'openReportsCount',
                TrainingQuestionReport::whereIn('status', [
                    TrainingQuestionReport::STATUS_OPEN,
                    TrainingQuestionReport::STATUS_IN_PROGRESS,
                ])->count()
            );

            // Bozze AI da rivedere (nel gioco sono nascoste dallo scope 'approved').
            $view->with(
                'pendingQuestionsCount',
                Question::withoutGlobalScope('approved')
                    ->where('review_status', Question::STATUS_PENDING)
                    ->count()
            );

            $view->with(
                'newEventRequestsCount',
                EventRequest::where('status', EventRequest::STATUS_NEW)->count()
            );
        });
    }
}
