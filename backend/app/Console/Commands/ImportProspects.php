<?php

namespace App\Console\Commands;

use App\Models\Prospect;
use App\Models\ProspectSearch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importa in "Clienti da contattare" i profili raccolti a mano su Instagram (solo dati pubblici di attivita').
 *
 * Formato del file JSON:
 *   {
 *     "search": { "name": "...", "region": "Campania", "city": "Napoli", "sector": "Abbigliamento", "searched_at": "2026-11-19" },
 *     "prospects": [
 *       { "handle": "@nome", "name": "...", "followers": 1200, "region": "...", "city": "...", "sector": "...", "bio": "...",
 *         "website_url": "...", "email": "...", "phone": "...", "has_physical_store": true, "orders_in_dm": false,
 *         "website_status": "none|link_only|outdated|yes|unknown", "activity": "active|inactive|unverified",
 *         "signals": "...", "proposal": "vetrina|ecommerce|prenotazioni|gestionale", "priority": "A|B|C" }
 *     ]
 *   }
 * Solo "handle" e' obbligatorio. Regione, citta' e settore mancanti si prendono dalla ricerca.
 * Doppioni (handle gia' presente o ripetuto nel file) scartati e elencati; con errori o --dry-run non si scrive nulla.
 * Nessun invio di messaggi: i contatti si fanno a mano.
 */
class ImportProspects extends Command
{
    protected $signature = 'app:import-prospects {file : Percorso del file JSON} {--dry-run : Mostra il report senza scrivere nulla}';

    protected $description = 'Importa profili Instagram di attivita\' in "Clienti da contattare", scartando i doppioni';

    public function handle(): int
    {
        $file = $this->argument('file');
        $dry = (bool) $this->option('dry-run');

        if (! is_file($file)) {
            $this->error("File non trovato: {$file}");

            return self::FAILURE;
        }

        $json = json_decode((string) file_get_contents($file), true);
        if (! is_array($json) || ! isset($json['prospects']) || ! is_array($json['prospects'])) {
            $this->error('JSON non valido: serve un oggetto con la lista "prospects".');

            return self::FAILURE;
        }

        $errors = [];
        $searchData = $this->validateSearch($json['search'] ?? null, $errors);

        // Normalizza e valida ogni profilo; i doppioni nel file si scartano.
        $rows = [];
        $skipped = [];
        $seen = [];
        foreach ($json['prospects'] as $i => $item) {
            $n = $i + 1;
            if (! is_array($item)) {
                $errors[] = "#{$n}: voce non valida";
                continue;
            }
            $handle = Prospect::normalizeHandle((string) ($item['handle'] ?? ''));
            $label = $handle !== '' ? "#{$n} (@{$handle})" : "#{$n}";

            [$row, $itemErrors] = $this->normalizeItem($item, $handle, $searchData);
            foreach ($itemErrors as $msg) {
                $errors[] = "{$label}: {$msg}";
            }
            if ($itemErrors) {
                continue;
            }

            if (isset($seen[$handle])) {
                $skipped[] = [$handle, 'ripetuto nello stesso file'];
                continue;
            }
            $seen[$handle] = true;
            $rows[$handle] = $row;
        }

        if ($errors) {
            $this->error('Errori nel file, nessuna scrittura:');
            foreach ($errors as $e) {
                $this->line(" - {$e}");
            }

            return self::FAILURE;
        }

        // Doppioni rispetto a cio' che e' gia' in elenco.
        $existing = $rows ? Prospect::whereIn('instagram_handle', array_keys($rows))->pluck('instagram_handle')->all() : [];
        foreach ($existing as $handle) {
            $skipped[] = [$handle, 'gia\' presente in elenco'];
            unset($rows[$handle]);
        }

        $searchExists = $searchData ? ProspectSearch::where('name', $searchData['name'])->exists() : false;

        if (! $dry && $rows) {
            DB::transaction(function () use ($rows, $searchData) {
                $search = $searchData ? ProspectSearch::firstOrCreate(['name' => $searchData['name']], $searchData) : null;
                foreach ($rows as $row) {
                    Prospect::create($row + ['prospect_search_id' => $search?->id]);
                }
            });
        } elseif (! $dry && $searchData && ! $searchExists) {
            // Nessun profilo nuovo: la ricerca si crea comunque, cosi' risulta "fatta" anche se tutto era gia' in elenco.
            ProspectSearch::firstOrCreate(['name' => $searchData['name']], $searchData);
        }

        $this->report($rows, $skipped, $searchData, $searchExists, $dry);

        return self::SUCCESS;
    }

    /** @return array<string, mixed>|null */
    private function validateSearch(mixed $search, array &$errors): ?array
    {
        if ($search === null) {
            return null;
        }
        if (! is_array($search)) {
            $errors[] = '"search": deve essere un oggetto';

            return null;
        }

        $region = $this->str($search['region'] ?? null);
        $city = $this->str($search['city'] ?? null);
        $sector = $this->str($search['sector'] ?? null);
        $date = $this->str($search['searched_at'] ?? null);

        if ($sector === null) {
            $errors[] = '"search": il settore e\' obbligatorio';
        }
        if ($region !== null && ! in_array($region, Prospect::REGIONS, true)) {
            $errors[] = "\"search\": regione \"{$region}\" non riconosciuta";
        }
        if ($date !== null && (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false)) {
            $errors[] = '"search": "searched_at" deve essere nel formato AAAA-MM-GG';
            $date = null;
        }
        if ($errors) {
            return null;
        }

        $name = $this->str($search['name'] ?? null)
            ?? collect([$region, $city, $sector, ($date ? \Carbon\Carbon::parse($date) : now())->format('d/m/Y')])->filter()->implode(' · ');

        return [
            'name' => mb_substr($name, 0, 150),
            'region' => $region,
            'city' => $city,
            'sector' => $sector,
            'status' => 'in_progress',
            'searched_at' => $date ?? today()->toDateString(),
        ];
    }

    /** @return array{0: array<string, mixed>, 1: list<string>} */
    private function normalizeItem(array $item, string $handle, ?array $search): array
    {
        $errors = [];

        if ($handle === '' || mb_strlen($handle) > 60 || ! preg_match('/^[a-z0-9._]+$/', $handle)) {
            $errors[] = 'handle mancante o non valido (solo lettere, numeri, punto e trattino basso)';
        }

        $followers = $item['followers'] ?? null;
        if ($followers !== null && (! is_int($followers) || $followers < 0)) {
            $errors[] = '"followers" deve essere un intero >= 0';
        }

        $websiteUrl = null;
        $rawUrl = $this->str($item['website_url'] ?? null);
        if ($rawUrl !== null) {
            $websiteUrl = Prospect::safeUrl($rawUrl);
            if ($websiteUrl === null || mb_strlen($websiteUrl) > 255) {
                $errors[] = "\"website_url\" non valido (solo http/https): {$rawUrl}";
                $websiteUrl = null;
            }
        }

        $email = $this->str($item['email'] ?? null);
        if ($email !== null && (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190)) {
            $errors[] = "\"email\" non valida: {$email}";
        }

        $region = $this->str($item['region'] ?? null) ?? $search['region'] ?? null;
        if ($region !== null && ! in_array($region, Prospect::REGIONS, true)) {
            $errors[] = "regione \"{$region}\" non riconosciuta";
        }

        $enum = function (string $key, array $allowed, ?string $default) use ($item, &$errors): ?string {
            $v = $this->str($item[$key] ?? null);
            if ($v === null) {
                return $default;
            }
            if (! array_key_exists($v, $allowed)) {
                $errors[] = "\"{$key}\" non valido: {$v} (ammessi: " . implode(', ', array_keys($allowed)) . ')';

                return $default;
            }

            return $v;
        };
        $websiteStatus = $enum('website_status', Prospect::WEBSITE_STATUSES, 'unknown');
        $activity = $enum('activity', Prospect::ACTIVITIES, 'unverified');
        $proposal = $enum('proposal', Prospect::PROPOSALS, null);
        $priority = $enum('priority', Prospect::PRIORITIES, 'B');

        $bool = function (string $key) use ($item, &$errors): ?bool {
            $v = $item[$key] ?? null;
            if ($v === null) {
                return null;
            }
            if (! is_bool($v)) {
                $errors[] = "\"{$key}\" deve essere true o false";

                return null;
            }

            return $v;
        };

        $limited = function (string $key, int $max, mixed $fallback = null) use ($item, &$errors): ?string {
            $v = $this->str($item[$key] ?? null) ?? $fallback;
            if ($v !== null && mb_strlen($v) > $max) {
                $errors[] = "\"{$key}\" supera {$max} caratteri";

                return null;
            }

            return $v;
        };

        $row = [
            'instagram_handle' => $handle,
            'name' => $limited('name', 150),
            'followers' => $followers,
            'region' => $region,
            'city' => $limited('city', 80, $search['city'] ?? null),
            'sector' => $limited('sector', 60, $search['sector'] ?? null),
            'bio' => $limited('bio', 2000),
            'website_url' => $websiteUrl,
            'email' => $email,
            'phone' => $limited('phone', 40),
            'has_physical_store' => $bool('has_physical_store'),
            'orders_in_dm' => $bool('orders_in_dm'),
            'website_status' => $websiteStatus,
            'activity' => $activity,
            'signals' => $limited('signals', 2000),
            'proposal' => $proposal,
            'priority' => $priority,
            'status' => 'to_contact',
        ];

        return [$row, $errors];
    }

    private function str(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function report(array $rows, array $skipped, ?array $search, bool $searchExists, bool $dry): void
    {
        $this->info($dry ? 'DRY-RUN: nessuna scrittura.' : 'Importati (stato "Da contattare").');
        if ($search) {
            $this->line('Ricerca: ' . $search['name'] . ($searchExists ? ' (esistente, riusata)' : ($dry ? ' (verra\' creata)' : ' (creata, in corso)')));
        } else {
            $this->line('Ricerca: nessuna (profili senza ricerca collegata)');
        }

        $table = [];
        foreach ($rows as $handle => $r) {
            $table[] = [
                '@' . $handle,
                $r['followers'] ?? '-',
                $r['city'] ?? '-',
                $r['sector'] ?? '-',
                $r['website_status'],
                $r['activity'],
                $r['priority'],
            ];
        }
        if ($table) {
            $this->table(['Profilo', 'Follower', 'Citta', 'Settore', 'Sito', 'Attivita', 'Priorita'], $table);
        }

        $this->line('Nuovi profili: ' . count($rows) . ' | scartati come doppioni: ' . count($skipped));
        foreach ($skipped as [$handle, $why]) {
            $this->warn(" scartato @{$handle} -> {$why}");
        }
    }
}
