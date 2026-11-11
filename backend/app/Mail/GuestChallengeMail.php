<?php

namespace App\Mail;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Mail\Mailable;

class GuestChallengeMail extends Mailable
{
    public function __construct(public Challenge $challenge, public User $challenger, public string $link) {}

    public function build()
    {
        return $this
            ->subject($this->challenger->nickname.' ti sfida a un quiz su Midalot ⚔️')
            ->view('emails.guest-challenge');
    }
}
