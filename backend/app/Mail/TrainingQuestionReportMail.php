<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class TrainingQuestionReportMail extends Mailable
{
    public function __construct(public array $report) {}

    public function build()
    {
        return $this
            ->subject('Segnalazione domanda training - Midalot')
            ->view('emails.training-question-report');
    }
}
