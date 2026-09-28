<?php

namespace App\Console\Commands;

use App\Mail\MidalarioReminderMail;
use App\Models\Quiz;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMidalarioReminders extends Command
{
    protected $signature = 'app:send-midalario-reminders';

    protected $description = 'Invia agli iscritti il promemoria dei Midalario che iniziano entro un\'ora.';

    public function handle(): int
    {
        $quizzes = Quiz::where('type', 'midalario')
            ->where('is_active', true)
            ->whereIn('midalario_status', ['open', 'closed'])
            ->whereBetween('midalario_scheduled_at', [now(), now()->addHour()])
            ->where(function ($q) {
                $q->whereNull('midalario_reminder_sent_for')
                    ->orWhereColumn('midalario_reminder_sent_for', '!=', 'midalario_scheduled_at');
            })
            ->with('participants.user')
            ->get();

        foreach ($quizzes as $quiz) {
            // Segnato prima dell'invio: meglio perdere un'email su un errore che mandarle doppie al giro successivo.
            $quiz->update(['midalario_reminder_sent_for' => $quiz->midalario_scheduled_at]);

            $sent = 0;

            foreach ($quiz->participants as $participant) {
                if (! $participant->user?->email) {
                    continue;
                }

                try {
                    Mail::to($participant->user->email)->send(new MidalarioReminderMail($quiz, $participant->user));
                    $sent++;
                } catch (Throwable $e) {
                    Log::error('Promemoria Midalario non inviato', [
                        'quiz_id' => $quiz->id,
                        'user_id' => $participant->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Midalario {$quiz->id} \"{$quiz->title}\": promemoria inviato a {$sent}/{$quiz->participants->count()} iscritti.");
        }

        return self::SUCCESS;
    }
}
