<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\TrainingCategory;
use App\Models\User;
use App\Models\XpEvent;
use App\Services\ChallengeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pannello di controllo delle sfide: chi sfida chi, come stanno andando,
 * quanti inviti vengono accettati e giocati.
 */
class ChallengeController extends Controller
{
    private const STATUSES = ['open', 'completed', 'expired', 'declined', 'cancelled'];

    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $mode = $request->get('mode', 'all');
        $search = trim((string) $request->get('search', ''));

        $challenges = Challenge::with(['challenger', 'participants.user', 'attempts'])
            ->when(in_array($status, self::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when(in_array($mode, ['async', 'live'], true), fn ($q) => $q->where('mode', $mode))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

                $q->whereHas('participants.user', fn ($u) => $u->where('nickname', 'like', $like)->orWhere('name', 'like', $like));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.challenges.index', [
            'challenges' => $challenges,
            'status' => $status,
            'mode' => $mode,
            'search' => $search,
            'stats' => $this->stats(),
            'categoryNames' => TrainingCategory::pluck('name', 'id'),
        ]);
    }

    public function show(Challenge $challenge)
    {
        $challenge->load(['challenger', 'participants.user', 'attempts.answers']);

        $ranked = app(ChallengeService::class)->ranking($challenge->attempts);

        $questions = $challenge->questions()
            ->with(['answers', 'quiz.trainingCategory', 'quiz.trainingSubcategory'])
            ->get();

        $xpByUser = XpEvent::where('source', 'challenge')
            ->where('source_id', $challenge->id)
            ->pluck('xp', 'user_id');

        return view('admin.challenges.show', [
            'challenge' => $challenge,
            'ranked' => $ranked,
            'questions' => $questions,
            'xpByUser' => $xpByUser,
            'categoryNames' => TrainingCategory::pluck('name', 'id'),
        ]);
    }

    private function stats(): array
    {
        $byStatus = Challenge::select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $byMode = Challenge::select('mode', DB::raw('COUNT(*) as total'))->groupBy('mode')->pluck('total', 'mode');

        $invites = DB::table('challenge_participants as cp')
            ->join('challenges as c', 'c.id', '=', 'cp.challenge_id')
            ->whereColumn('cp.user_id', '!=', 'c.challenger_id');

        $invitesByStatus = (clone $invites)
            ->select('cp.status', DB::raw('COUNT(*) as total'))
            ->groupBy('cp.status')
            ->pluck('total', 'status');

        $invitesTotal = (int) $invitesByStatus->sum();

        $invitesPlayed = DB::table('challenge_attempts as a')
            ->join('challenges as c', 'c.id', '=', 'a.challenge_id')
            ->whereColumn('a.user_id', '!=', 'c.challenger_id')
            ->where('a.completed', true)
            ->count();

        $participantsPerChallenge = DB::table('challenge_participants')
            ->select('challenge_id', DB::raw('COUNT(*) as total'))
            ->groupBy('challenge_id')
            ->get();

        $topChallengers = DB::table('challenges')
            ->join('users', 'users.id', '=', 'challenges.challenger_id')
            ->select('users.id', 'users.nickname', DB::raw('COUNT(*) as total'))
            ->groupBy('users.id', 'users.nickname')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topWinners = DB::table('challenges')
            ->join('users', 'users.id', '=', 'challenges.winner_id')
            ->select('users.id', 'users.nickname', DB::raw('COUNT(*) as total'))
            ->groupBy('users.id', 'users.nickname')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'total' => (int) $byStatus->sum(),
            'by_status' => $byStatus,
            'by_mode' => $byMode,
            'last_7_days' => Challenge::where('created_at', '>=', now()->subDays(7))->count(),
            'today' => Challenge::where('created_at', '>=', now()->startOfDay())->count(),
            'invites_total' => $invitesTotal,
            'invites_by_status' => $invitesByStatus,
            'invites_played' => $invitesPlayed,
            'play_rate' => $invitesTotal > 0 ? round($invitesPlayed / $invitesTotal * 100) : null,
            'avg_participants' => $participantsPerChallenge->isNotEmpty() ? round($participantsPerChallenge->avg('total'), 1) : null,
            'group_challenges' => $participantsPerChallenge->where('total', '>', 2)->count(),
            'unique_challengers' => DB::table('challenges')->distinct('challenger_id')->count('challenger_id'),
            'unique_players' => DB::table('challenge_attempts')->distinct('user_id')->count('user_id'),
            'xp_total' => (int) XpEvent::where('source', 'challenge')->sum('xp'),
            'opted_out' => User::where('accepts_challenges', false)->count(),
            'top_challengers' => $topChallengers,
            'top_winners' => $topWinners,
        ];
    }
}
