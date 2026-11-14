<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class EventRequestMail extends Mailable
{
    public function __construct(public array $eventRequest) {}

    public function build()
    {
        $subject = ($this->eventRequest['kind'] ?? 'evento') === 'servizi'
            ? 'Nuova richiesta servizi digitali (sito, e-commerce, gestionale) - Midalot'
            : 'Nuova richiesta quiz personalizzato per evento - Midalot';

        return $this
            ->subject($subject)
            ->view('emails.event-request');
    }
}
