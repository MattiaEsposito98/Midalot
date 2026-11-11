<?php

namespace App\Services;

use App\Models\AvatarItem;
use App\Models\CoinEvent;
use App\Models\User;
use App\Models\UserAvatarItem;
use Illuminate\Support\Str;

/**
 * Invita un amico: codice personale, collegamento alla registrazione e premi
 * in monete quando l'amico gioca davvero (config/referral.php).
 */
class ReferralService
{
    /**
     * Codice personale dell'utente (creato la prima volta che serve).
     */
    public function code(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        do {
            $code = Str::upper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        $user->forceFill(['referral_code' => $code])->save();

        return $code;
    }

    public function link(User $user): string
    {
        return rtrim(config('app.frontend_url'), '/').'/register?ref='.$this->code($user);
    }

    /**
     * Collega un nuovo utente a chi lo ha invitato (codice valido, non se stesso).
     */
    public function attach(User $newUser, ?string $code): void
    {
        $code = Str::upper(trim((string) $code));
        if ($code === '' || $newUser->referred_by) {
            return;
        }

        $inviter = User::where('referral_code', $code)->first();
        if ($inviter && $inviter->id !== $newUser->id) {
            $newUser->forceFill(['referred_by' => $inviter->id])->save();
        }
    }

    /**
     * Premi dopo ogni accredito di XP: benvenuto all'amico, monete a chi lo ha
     * invitato alle soglie di livello, titolo "Ambasciatore". Tutto idempotente.
     */
    public function check(int $userId, int $level): void
    {
        $user = User::find($userId, ['id', 'referred_by']);
        if (! $user || ! $user->referred_by) {
            return;
        }

        $avatar = app(AvatarService::class);
        $avatar->addCoins($userId, 'referral_welcome', $userId, (int) config('referral.welcome_coins', 0));

        foreach (config('referral.level_rewards', []) as $minLevel => $coins) {
            if ($level < $minLevel) {
                continue;
            }

            $source = 'referral_l'.$minLevel;
            if (CoinEvent::where('user_id', $user->referred_by)->where('source', $source)->where('source_id', $userId)->exists()) {
                continue;
            }

            $thisMonth = CoinEvent::where('user_id', $user->referred_by)->where('source', $source)
                ->where('created_at', '>=', now()->startOfMonth())->count();
            if ($thisMonth >= (int) config('referral.monthly_cap', 10)) {
                continue;
            }

            $avatar->addCoins($user->referred_by, $source, $userId, (int) $coins);
        }

        $this->grantAmbassador($user->referred_by);
    }

    private function grantAmbassador(int $inviterId): void
    {
        $needed = (int) config('referral.ambassador_friends', 3);
        $firstLevel = (int) array_key_first(config('referral.level_rewards', [3 => 0]));
        $friends = CoinEvent::where('user_id', $inviterId)->where('source', 'referral_l'.$firstLevel)->count();

        if ($friends < $needed) {
            return;
        }

        $itemId = AvatarItem::where('key', config('referral.ambassador_item'))->value('id');
        if ($itemId) {
            UserAvatarItem::insertOrIgnore([
                'user_id' => $inviterId,
                'avatar_item_id' => $itemId,
                'source' => 'referral',
                'seen_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Amici invitati con livello e monete guadagnate.
     */
    public function summary(User $user): array
    {
        $friends = User::where('referred_by', $user->id)->orderByDesc('id')
            ->get(['id', 'nickname', 'xp_total', 'email_verified_at', 'avatar_url', 'avatar_frame', 'avatar_title', 'created_at']);
        $earned = CoinEvent::where('user_id', $user->id)->where('source', 'like', 'referral_l%')->get(['source', 'source_id', 'amount']);

        return [
            'link' => $this->link($user),
            'code' => $this->code($user),
            'welcome_coins' => (int) config('referral.welcome_coins'),
            'level_rewards' => collect(config('referral.level_rewards'))->map(fn ($c, $l) => ['level' => (int) $l, 'coins' => (int) $c])->values(),
            'monthly_cap' => (int) config('referral.monthly_cap'),
            'ambassador_friends' => (int) config('referral.ambassador_friends'),
            'coins_earned' => (int) $earned->sum('amount'),
            'friends' => $friends->map(fn (User $f) => [
                'nickname' => $f->nickname,
                'level' => XpService::info((int) $f->xp_total)['level'],
                'verified' => $f->email_verified_at !== null,
                'joined_at' => $f->created_at?->toDateString(),
                'coins' => (int) $earned->where('source_id', $f->id)->sum('amount'),
                ...$f->publicAvatar(),
            ])->values(),
        ];
    }
}
