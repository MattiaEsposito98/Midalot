<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

class InviteFriendMail extends Mailable
{
    public function __construct(public User $inviter, public string $link) {}

    public function build()
    {
        return $this
            ->subject($this->inviter->nickname.' ti invita a giocare su Midalot 🎉')
            ->view('emails.invite-friend');
    }
}
