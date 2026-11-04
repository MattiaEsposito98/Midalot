<?php

namespace App\Mail;

use App\Models\Challenge;
use Illuminate\Mail\Mailable;

class ChallengeInviteMail extends Mailable
{
    public string $challengeUrl;

    public function __construct(public Challenge $challenge)
    {
        $this->challengeUrl = rtrim(config('app.frontend_url'), '/').'/sfide/'.$challenge->id;
    }

    public function build()
    {
        return $this
            ->subject($this->challenge->challenger->nickname.' ti ha sfidato su Midalot ⚔️')
            ->view('emails.challenge-invite');
    }
}
