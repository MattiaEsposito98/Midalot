<?php

namespace App\Support;

use App\Models\Prospect;
use Illuminate\Support\Str;

/**
 * Bozza del primo messaggio da mandare A MANO a un profilo ("Clienti da contattare").
 * Instagram non permette di precompilare un messaggio (a differenza di WhatsApp): si copia e si incolla.
 *
 * Posizionamento: facciamo SITI WEB su misura che possono aiutare le attività a vendere online.
 * Niente marketing, niente promesse di vendite. Il link porta alla pagina servizi di midalot.live con utm (vedi trackedLink).
 * Usa solo ciò che sappiamo del profilo (nome, città, negozio fisico, ordini in DM, stato del sito verificato),
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
            "Realizziamo siti web su misura per le attività, pensati per farsi trovare e vendere online. Qui vedi cosa facciamo: " . self::trackedLink($p),
            self::situation($p),
            "Se ti va ne parliamo, senza impegno e con preventivo gratuito. "
                . "Se non ti interessa, dimmelo e non ti scrivo più. Grazie e buona giornata!",
        ];

        return implode("\n\n", array_filter($parts));
    }

    /**
     * Link alla pagina dei servizi con i parametri per Google Analytics (sorgente instagram / mezzo dm).
     * Il settore sta in utm_campaign perche' il sito invia a Google solo utm_source, utm_medium e utm_campaign.
     * Nessun nome o profilo nell'indirizzo: solo l'etichetta del settore.
     */
    public static function trackedLink(Prospect $p): string
    {
        $sector = Str::slug((string) $p->sector, '_');
        $campaign = 'clienti' . ($sector !== '' ? '_' . Str::limit($sector, 28, '') : '');

        return 'https://midalot.live/servizi-digitali?' . http_build_query([
            'utm_source' => 'instagram',
            'utm_medium' => 'dm',
            'utm_campaign' => $campaign,
        ]);
    }

    /** Il pezzo "perché proprio te", in base a cosa abbiamo verificato. */
    private static function situation(Prospect $p): string
    {
        if ($p->website_status === 'broken') {
            return "Ho provato ad aprire il sito indicato nel tuo profilo ma al momento non si apre correttamente: peccato, perché chi ti cerca online si ferma lì. Potremmo sistemarlo o rifarlo da zero.";
        }

        if ($p->website_status === 'yes') {
            return "Vedo che hai già un sito: se in futuro vuoi rinnovarlo o migliorarlo per vendere meglio online, siamo a disposizione.";
        }

        if ($p->website_status === 'outdated') {
            return "Il sito che hai potrebbe essere rinnovato: oggi la maggior parte dei clienti arriva dal telefono, e un sito moderno e veloce aiuta a convincerli e a farti trovare su Google.";
        }

        if (in_array($p->website_status, ['none', 'link_only'], true)) {
            if ($p->orders_in_dm) {
                return "Ho notato che gli ordini li gestisci direttamente in messaggio: un sito semplice con il catalogo può farti risparmiare tempo e farti trovare anche da chi non ti segue su Instagram.";
            }

            return match ($p->proposal) {
                'ecommerce' => "Online non vedo ancora un sito tuo: un negozio online potrebbe aiutarti a vendere anche fuori orario e fuori città.",
                'prenotazioni' => "Online non vedo ancora un sito tuo: un sito con prenotazioni online potrebbe farti risparmiare telefonate e messaggi.",
                default => "Online non vedo ancora un sito tuo: un sito vetrina moderno, pensato per lo smartphone, potrebbe farti trovare su Google con indirizzo, orari e prodotti.",
            };
        }

        return "Se ti interessa, un sito pensato per la tua attività può aiutarti a farti conoscere e a vendere online.";
    }

    /** Nome breve per il saluto: senza il testo tra parentesi; se manca, il nome del profilo. */
    public static function displayName(Prospect $p): string
    {
        $name = trim((string) preg_replace('/\s*\(.*?\)\s*/u', ' ', (string) $p->name));

        return $name !== '' ? $name : $p->instagram_handle;
    }
}
