<?php

namespace App\Support;

use App\Models\Prospect;

/**
 * Bozza del primo messaggio da mandare A MANO a un profilo ("Clienti da contattare").
 * Usa solo ciò che sappiamo del profilo (nome, città, negozio fisico, ordini in DM, situazione del sito),
 * mai cose non verificate. L'apertura cambia da profilo a profilo (in modo stabile) così non si manda
 * sempre lo stesso identico testo. Spiega chi siamo, perché scriviamo e come tirarsi fuori.
 */
class ProspectMessage
{
    public static function for(Prospect $p): string
    {
        $name = self::displayName($p);
        $hook = ($p->has_physical_store && $p->city) ? " e il tuo negozio a {$p->city}" : '';

        $openings = [
            "Ciao {$name}, ti scrivo da Midalot (@mida.lot): ho visto il tuo profilo{$hook}.",
            "Buongiorno {$name}, siamo Midalot (@mida.lot). Abbiamo visto il tuo profilo{$hook} e volevamo presentarci.",
            "Ciao {$name}! Siamo Midalot (@mida.lot) e ci è capitato di vedere il tuo profilo{$hook}.",
        ];
        $opening = $openings[crc32($p->instagram_handle) % count($openings)];

        $parts = [
            $opening,
            "Oltre ai quiz e ai contest per eventi, realizziamo siti web, e-commerce e gestionali su misura per le attività.",
            self::situation($p),
            "Se ti va possiamo capire insieme cosa ti serve, senza impegno e con preventivo gratuito. "
                . "Se invece non ti interessa nessun problema: dimmelo e non ti scrivo più. Grazie e buona giornata!",
        ];

        return implode("\n\n", array_filter($parts));
    }

    /** Il pezzo "perché proprio te", in base a cosa abbiamo visto. */
    private static function situation(Prospect $p): string
    {
        if ($p->website_status === 'yes') {
            return "Vedo che hai già un sito: se in futuro ti serve una mano per vendere meglio online o per gestire ordini e clienti con uno strumento fatto su misura, siamo a disposizione.";
        }

        if ($p->website_status === 'outdated') {
            return "Il sito che hai potrebbe essere rinnovato: oggi la maggior parte dei clienti arriva dal telefono, e un sito moderno e veloce aiuta a convincerli e a farti trovare su Google.";
        }

        if (in_array($p->website_status, ['none', 'link_only'], true)) {
            if ($p->orders_in_dm) {
                return "Ho notato che gli ordini li gestisci direttamente in messaggio: un sito semplice con catalogo e richiesta d'ordine può farti risparmiare tempo e farti trovare anche da chi non ti segue su Instagram.";
            }

            return match ($p->proposal) {
                'ecommerce' => "Online non vedo ancora un sito vero e proprio: un negozio online potrebbe aiutarti a vendere anche fuori orario e fuori città.",
                'prenotazioni' => "Online non vedo ancora un sito vero e proprio: un sito con prenotazioni online potrebbe farti risparmiare telefonate e messaggi.",
                default => "Online non vedo ancora un sito vero e proprio: un sito vetrina moderno, pensato per lo smartphone, potrebbe farti trovare su Google con indirizzo, orari e prodotti.",
            };
        }

        return "Se ti interessa, un sito pensato per la tua attività può aiutarti a farti conoscere e a vendere di più online.";
    }

    /** Nome breve per il saluto: senza il testo tra parentesi; se manca, il nome del profilo. */
    public static function displayName(Prospect $p): string
    {
        $name = trim((string) preg_replace('/\s*\(.*?\)\s*/u', ' ', (string) $p->name));

        return $name !== '' ? $name : $p->instagram_handle;
    }
}
