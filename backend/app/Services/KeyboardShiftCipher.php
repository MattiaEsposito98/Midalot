<?php

namespace App\Services;

class KeyboardShiftCipher
{
    private const ROWS = [
        'QWERTYUIOP',
        'ASDFGHJKL',
        'ZXCVBNM',
    ];

    /**
     * Trasla ogni lettera di $word di $shift posizioni sulla stessa riga
     * della tastiera QWERTY a cui appartiene (wrap-around circolare).
     * I caratteri non presenti in nessuna riga restano invariati.
     */
    public static function encode(string $word, int $shift): string
    {
        $word = mb_strtoupper($word);
        $result = '';

        foreach (mb_str_split($word) as $char) {
            $result .= self::shiftChar($char, $shift);
        }

        return $result;
    }

    private static function shiftChar(string $char, int $shift): string
    {
        foreach (self::ROWS as $row) {
            $pos = mb_strpos($row, $char);

            if ($pos !== false) {
                $len = mb_strlen($row);
                $newPos = (($pos + $shift) % $len + $len) % $len;

                return mb_substr($row, $newPos, 1);
            }
        }

        return $char;
    }
}
