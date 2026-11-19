<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prospect extends Model
{
    public const STATUSES = [
        'to_contact' => 'Da contattare',
        'contacted' => 'Contattato',
        'replied' => 'Ha risposto',
        'interested' => 'Interessato',
        'quote_sent' => 'Preventivo inviato',
        'client' => 'Cliente',
        'not_suitable' => 'Non idoneo',
    ];

    /** Stati "chiusi": non servono promemoria. */
    public const CLOSED_STATUSES = ['client', 'not_suitable'];

    public const WEBSITE_STATUSES = [
        'unknown' => 'Da verificare',
        'none' => 'Nessun sito',
        'link_only' => 'Solo link (Linktree ecc.)',
        'outdated' => 'Sito datato',
        'broken' => 'Sito non funziona',
        'yes' => 'Ha un sito',
    ];

    public const ACTIVITIES = [
        'unverified' => 'Da verificare',
        'active' => 'Attivo',
        'inactive' => 'Poco attivo',
    ];

    public const PROPOSALS = [
        'vetrina' => 'Sito vetrina',
        'ecommerce' => 'E-commerce',
        'prenotazioni' => 'Prenotazioni',
        'gestionale' => 'Gestionale',
    ];

    public const PRIORITIES = ['A' => 'A (alta)', 'B' => 'B (media)', 'C' => 'C (bassa)'];

    /** Settori proposti nei menu a tendina (si aggiungono a quelli gia' usati nel DB). */
    public const SECTORS = [
        'Abbigliamento', 'Calzature', 'Gioielleria', 'Ottica', 'Parrucchieri e barbieri', 'Estetica e benessere',
        'Palestre e sport', 'Ristoranti e pizzerie', 'Pasticcerie e bar', 'Fiorai', 'B&B e strutture ricettive',
        'Arredamento e casa', 'Auto e officine', 'Animali', 'Fotografia ed eventi', 'Altro',
    ];

    /** Elenco dei settori per i menu: quelli fissi piu' quelli gia' presenti in profili e ricerche. */
    public static function sectorOptions(): array
    {
        $used = static::whereNotNull('sector')->where('sector', '!=', '')->distinct()->pluck('sector')
            ->merge(ProspectSearch::whereNotNull('sector')->where('sector', '!=', '')->distinct()->pluck('sector'));

        return collect(self::SECTORS)->merge($used)->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }

    public const REGIONS = [
        'Abruzzo', 'Basilicata', 'Calabria', 'Campania', 'Emilia-Romagna', 'Friuli-Venezia Giulia',
        'Lazio', 'Liguria', 'Lombardia', 'Marche', 'Molise', 'Piemonte', 'Puglia', 'Sardegna',
        'Sicilia', 'Toscana', 'Trentino-Alto Adige', 'Umbria', 'Valle d\'Aosta', 'Veneto',
    ];

    protected $fillable = [
        'prospect_search_id', 'instagram_handle', 'name', 'followers', 'region', 'city', 'sector', 'bio',
        'website_url', 'email', 'phone', 'has_physical_store', 'orders_in_dm', 'website_status', 'activity',
        'signals', 'proposal', 'priority', 'status', 'contacted_at', 'contacted_by', 'follow_up_at', 'notes',
    ];

    protected $casts = [
        'followers' => 'integer',
        'has_physical_store' => 'boolean',
        'orders_in_dm' => 'boolean',
        'contacted_at' => 'datetime',
        'follow_up_at' => 'date',
    ];

    public function search(): BelongsTo
    {
        return $this->belongsTo(ProspectSearch::class, 'prospect_search_id');
    }

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by');
    }

    /** "@Nome", URL del profilo o nome semplice -> "nome" (minuscolo, senza @, senza parametri). */
    public static function normalizeHandle(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $value);
        $value = preg_replace('#[/?\#].*$#', '', $value);

        return strtolower(ltrim($value, '@'));
    }

    /** Solo indirizzi http/https (mai javascript: o altro): null se non valido. */
    public static function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        // Qualsiasi altro schema (ftp://, javascript:, data:, mailto: ...) viene scartato, non "corretto".
        if (preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url) && ! preg_match('#^https?://#i', $url)) {
            return null;
        }
        if (preg_match('#^(javascript|data|vbscript|file|mailto|tel):#i', $url)) {
            return null;
        }
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url) ? $url : null;
    }

    public function getInstagramUrlAttribute(): string
    {
        return 'https://www.instagram.com/' . $this->instagram_handle . '/';
    }

    /** Link che apre direttamente la chat Instagram con questo profilo (se non funziona, resta il link al profilo). */
    public function getInstagramChatUrlAttribute(): string
    {
        return 'https://ig.me/m/' . $this->instagram_handle;
    }

    /** Bozza del primo messaggio personalizzato (da inviare a mano, mai in automatico). */
    public function getOutreachMessageAttribute(): string
    {
        return \App\Support\ProspectMessage::for($this);
    }

    public function isFollowUpDue(): bool
    {
        return $this->follow_up_at !== null
            && $this->follow_up_at->lte(today())
            && ! in_array($this->status, self::CLOSED_STATUSES, true);
    }
}
