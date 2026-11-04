<?php

namespace App\Mail;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Mail\Mailable;

class ChallengeInviteMail extends Mailable
{
    public string $challengeUrl;

    /**
     * @param  string[]  $otherInvitees  nickname degli altri invitati alla stessa sfida
     */
    public function __construct(public Challenge $challenge, public User $invitee, public array $otherInvitees = [])
    {
        $path = $challenge->mode === 'live' ? '/live' : '';
        $this->challengeUrl = rtrim(config('app.frontend_url'), '/').'/sfide/'.$challenge->id.$path;
    }

    public function build()
    {
        $live = $this->challenge->mode === 'live';

        return $this
            ->subject($this->challenge->challenger->nickname.($live ? ' ti sfida LIVE su Midalot ⚡' : ' ti ha sfidato su Midalot ⚔️'))
            ->view('emails.challenge-invite');
    }
}
