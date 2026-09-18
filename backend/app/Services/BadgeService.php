<?php

namespace App\Services;

use App\Models\MidalarioBadge;
use App\Models\MonthlyBadge;
use Illuminate\Support\Collection;

/**
 * Calcola i badge (mensile + Midalario) di un gruppo di utenti in blocco,
 * per i contesti che lavorano su righe di query grezze invece che su
 * modelli Eloquent con relazioni eager-load (es. PeriodLeaderboardService,
 * che aggrega da una subquery SQL e non da $user->load(...)).
 */
class BadgeService
{
    /**
     * @param  iterable<int>  $userIds
     * @return Collection<int, array<int, array{type: string, label: string}>> chiave = user_id
     */
    public static function forUserIds(iterable $userIds): Collection
    {
        $ids = collect($userIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $monthlyByUser = MonthlyBadge::whereIn('user_id', $ids)
            ->orderByDesc('month')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($badges) => $badges->first());

        $midalarioByUser = MidalarioBadge::whereIn('user_id', $ids)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id');

        return $ids->mapWithKeys(function ($userId) use ($monthlyByUser, $midalarioByUser) {
            $badges = [];

            if ($monthly = $monthlyByUser->get($userId)) {
                $badges[] = ['type' => 'monthly', 'label' => $monthly->label];
            }

            foreach ($midalarioByUser->get($userId, collect()) as $midalarioBadge) {
                $badges[] = ['type' => 'midalario', 'label' => $midalarioBadge->label];
            }

            return [$userId => $badges];
        });
    }
}
