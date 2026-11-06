<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    public const QUESTIONS_COUNT = 7;

    // Sfidante compreso: lo sfidante invita al massimo MAX_PARTICIPANTS - 1 persone.
    public const MAX_PARTICIPANTS = 5;

    protected $fillable = [
        'challenger_id',
        // Primo invitato: resta per compatibilita', la verita' e' in challenge_participants.
        'opponent_id',
        'mode',
        'status',
        'category_ids',
        'subcategory_ids',
        'quiz_ids',
        'expires_at',
        'live_started_at',
        'completed_at',
        'winner_id',
    ];

    protected $casts = [
        'category_ids' => 'array',
        'subcategory_ids' => 'array',
        'quiz_ids' => 'array',
        'expires_at' => 'datetime',
        'live_started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function challenger()
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function participants()
    {
        return $this->hasMany(ChallengeParticipant::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'challenge_questions')
            ->withPivot('position')
            ->orderBy('challenge_questions.position');
    }

    public function attempts()
    {
        return $this->hasMany(ChallengeAttempt::class);
    }

    public function involves(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function isChallenger(int $userId): bool
    {
        return (int) $this->challenger_id === $userId;
    }

    /**
     * Etichette della selezione: categorie scelte in generale e sottocategorie
     * scelte nel dettaglio ("Disney › Peter Pan"). Vuoto = tutte le categorie.
     *
     * @return string[]
     */
    public function selectionLabels(): array
    {
        if (empty($this->category_ids) && empty($this->subcategory_ids) && empty($this->quiz_ids)) {
            return ['Tutte le categorie'];
        }

        $labels = [];

        if (! empty($this->category_ids)) {
            $labels = TrainingCategory::whereIn('id', $this->category_ids)->orderBy('name')->pluck('name')->all();
        }

        if (! empty($this->subcategory_ids)) {
            $subs = TrainingSubcategory::with('category')->whereIn('id', $this->subcategory_ids)->get()
                ->sortBy(fn ($s) => ($s->category?->name ?? '').' '.$s->name);

            foreach ($subs as $sub) {
                $labels[] = ($sub->category?->name ?? '?').' › '.$sub->name;
            }
        }

        if (! empty($this->quiz_ids)) {
            $quizzes = Quiz::with('trainingCategory')->whereIn('id', $this->quiz_ids)->get()
                ->sortBy(fn ($q) => ($q->trainingCategory?->name ?? '').' '.$q->title);

            foreach ($quizzes as $quiz) {
                $labels[] = ($quiz->trainingCategory?->name ?? '?').' › '.$quiz->title;
            }
        }

        return $labels;
    }

    /**
     * Le risposte corrette si possono vedere solo a sfida chiusa: finche' un
     * giocatore puo' ancora giocare, rivelarle permetterebbe di passarle
     * agli altri (es. con un secondo account).
     */
    public function isClosed(): bool
    {
        return $this->status !== 'open';
    }
}
