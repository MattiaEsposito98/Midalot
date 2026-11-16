<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Riconosce nickname con parolacce, bestemmie o nomi riservati (lista in
 * config/nickname_blocklist.php). Protezione minima, non perfetta: confronta le parole
 * dopo aver tolto separatori, tradotto i numeri "leet" e ridotto le lettere ripetute.
 */
class NicknameFilter
{
    private const LEET = ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's'];

    public static function isAllowed(?string $value): bool
    {
        return self::reason($value) === null;
    }

    /** Null se il nome va bene, altrimenti la parola che lo blocca (per log e test). */
    public static function reason(?string $value): ?string
    {
        $value = (string) $value;
        if (trim($value) === '') {
            return null;
        }

        $list = config('nickname_blocklist');
        $whole = self::squash($value);

        foreach ($list['contains'] ?? [] as $word) {
            if (str_contains($whole, self::squash($word))) {
                return $word;
            }
        }

        // Parole corte: solo se sono l'intero nome o un pezzo separato da . _ - spazi.
        $tokens = array_filter(array_map(
            fn ($t) => self::squash($t),
            preg_split('/[^\p{L}0-9@$]+/u', Str::lower($value)) ?: []
        ));
        $tokens[] = $whole;
        foreach ($list['words'] ?? [] as $word) {
            if (in_array(self::squash($word), $tokens, true)) {
                return $word;
            }
        }

        foreach ($list['sacred'] ?? [] as $sacred) {
            foreach ($list['insults'] ?? [] as $insult) {
                $a = self::squash($sacred);
                $b = self::squash($insult);
                if (str_contains($whole, $a.$b) || str_contains($whole, $b.$a)) {
                    return $insult.' '.$sacred;
                }
            }
        }

        return null;
    }

    /** "St_0ca__ZZO" -> "stocazo": minuscolo, senza accenti, leet tradotto, solo lettere, niente doppie. */
    public static function squash(string $value): string
    {
        $value = Str::lower(Str::ascii($value));
        $value = strtr($value, self::LEET);
        $value = preg_replace('/[^a-z]/', '', $value);

        return preg_replace('/(.)\1+/', '$1', $value);
    }
}
