<?php

namespace App\Providers;

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
        });
    }
}
