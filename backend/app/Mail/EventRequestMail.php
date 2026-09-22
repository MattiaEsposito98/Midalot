<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class EventRequestMail extends Mailable
{
    public function __construct(public array $eventRequest) {}

    public function build()
    {
        return $this
            ->subject('Nuova richiesta quiz personalizzato per evento - Midalot')
            ->view('emails.event-request');
    }
}
