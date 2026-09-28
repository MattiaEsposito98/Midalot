<?php

namespace App\Mail;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Mail\Mailable;

class MidalarioReminderMail extends Mailable
{
    public string $roomUrl;

    public function __construct(public Quiz $quiz, public User $user)
    {
        $this->roomUrl = rtrim(config('app.frontend_url'), '/').'/midalario/'.$quiz->id;
    }

    public function build()
    {
        return $this->subject('Il Midalario inizia alle '.$this->quiz->midalario_scheduled_at->format('H:i').' ⏰ - Midalot')
            ->view('emails.midalario-reminder');
    }
}
