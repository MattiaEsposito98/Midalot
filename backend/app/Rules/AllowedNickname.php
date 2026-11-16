<?php

namespace App\Rules;

use App\Support\NicknameFilter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Rifiuta nickname (o nomi) con parolacce, bestemmie o nomi riservati. */
class AllowedNickname implements ValidationRule
{
    public function __construct(private string $message = 'Questo nickname non è consentito: scegline un altro.')
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ! NicknameFilter::isAllowed($value)) {
            $fail($this->message);
        }
    }
}
