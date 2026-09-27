<?php

namespace App\Models\Concerns;

use Carbon\Carbon;

trait HasDisponibilita
{
    public function initializeHasDisponibilita(): void
    {
        $this->mergeCasts(['disponibile_fino_a' => 'datetime']);
        $this->mergeFillable(['disponibile_fino_a']);
    }

    public static function defaultDisponibileFinoA(): Carbon
    {
        return now()->endOfWeek(Carbon::SUNDAY)->setTime(23, 59);
    }

    public function isExpired(): bool
    {
        return $this->disponibile_fino_a !== null && $this->disponibile_fino_a->isPast();
    }

    public function isPlayable(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    // Scaduto in un mese precedente: resta nel DB (storico e classifiche) ma sparisce dalle liste di gioco.
    public function isArchived(): bool
    {
        return $this->disponibile_fino_a !== null && $this->disponibile_fino_a->lt(now()->startOfMonth());
    }
}
