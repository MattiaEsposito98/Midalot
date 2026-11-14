<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Services\MonthlyBadgeAssigner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registro dei premi in monete per posizione (classifica mensile e
 * Midalario): a chi, quanto e quando, piu' cosa e' in arrivo e cosa non e'
 * stato premiato. Sola lettura.
 */
class PrizeAwardController extends Controller
{
    public function __construct(private MonthlyBadgeAssigner $monthly)
    {
    }

    public function index(Request $request)
    {
        $kind = in_array($request->query('kind'), ['monthly', 'midalario'], true) ? $request->query('kind') : null;

        $summary = [];
        foreach (['monthly', 'midalario'] as $k) {
            $summary[$k] = [
                'events' => DB::table('prize_awards')->where('kind', $k)->distinct()->count('ref'),
                'awards' => DB::table('prize_awards')->where('kind', $k)->count(),
                'winners' => DB::table('prize_awards')->where('kind', $k)->distinct()->count('user_id'),
                'coins' => (int) DB::table('prize_awards')->where('kind', $k)->sum('coins'),
            ];
        }

        $rows = DB::table('prize_awards')
            ->join('users', 'users.id', '=', 'prize_awards.user_id')
            ->when($kind, fn ($q) => $q->where('prize_awards.kind', $kind))
            ->orderByDesc('prize_awards.created_at')
            ->orderBy('prize_awards.position')
            ->get([
                'prize_awards.kind', 'prize_awards.ref', 'prize_awards.label', 'prize_awards.position',
                'prize_awards.coins', 'prize_awards.seen_at', 'prize_awards.created_at',
                'users.id as user_id', 'users.nickname',
            ]);

        // Un blocco per evento (mese o sessione), dal piu' recente.
        $events = $rows->groupBy(fn ($r) => $r->kind.'|'.$r->ref)->map(fn ($group) => [
            'kind' => $group->first()->kind,
            'ref' => $group->first()->ref,
            'label' => $group->first()->label,
            'at' => $group->max('created_at'),
            'total' => $group->sum('coins'),
            'rows' => $group->sortBy('position')->values(),
        ])->sortByDesc('at')->values();

        // Midalario finiti che non hanno ricevuto premi (es. chiusi prima
        // dell'introduzione dei premi): l'admin vede cosa non e' stato premiato.
        $premiati = DB::table('prize_awards')->where('kind', 'midalario')->pluck('ref')->map(fn ($r) => (int) $r)->all();

        $unrewarded = Quiz::where('type', 'midalario')
            ->where('midalario_status', 'finished')
            ->whereNotIn('id', $premiati)
            ->withCount(['attempts as completed_count' => fn ($q) => $q->where('completed', true)])
            ->orderByDesc('id')
            ->get(['id', 'title', 'midalario_scheduled_at', 'midalario_started_at']);

        $targetMonth = $this->monthly->targetMonth();

        return view('admin.prize-awards.index', [
            'kind' => $kind,
            'summary' => $summary,
            'events' => $events,
            'unrewarded' => $unrewarded,
            'scale' => ['monthly' => config('prizes.monthly'), 'midalario' => config('prizes.midalario')],
            'targetMonth' => $targetMonth,
            'monthDone' => $this->monthly->alreadyRun($targetMonth),
            'monthPreview' => $this->monthly->prizePreview($targetMonth),
        ]);
    }
}
