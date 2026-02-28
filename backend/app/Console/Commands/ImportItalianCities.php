<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\City;

class ImportItalianCities extends Command
{
    protected $signature = 'cities:import-italy {--truncate}';
    protected $description = 'Importa comuni italiani da JSON con latitudine e longitudine';

    public function handle()
    {
        $path = storage_path('app/import/comuni.json');

        if (!file_exists($path)) {
            $this->error("File non trovato: $path");
            return Command::FAILURE;
        }

        $json = file_get_contents($path);

        // ✅ Rimuove BOM
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);

        // ✅ Corregge encoding misto (Windows-1252 → UTF-8)
        $json = mb_convert_encoding($json, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        $json = iconv('UTF-8', 'UTF-8//IGNORE', $json);

        $cities = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Errore JSON: " . json_last_error_msg());
            return Command::FAILURE;
        }

        if (!is_array($cities)) {
            $this->error("Il JSON non contiene un array valido.");
            return Command::FAILURE;
        }

        if ($this->option('truncate')) {
            $this->info("Pulizia tabella cities...");
            City::truncate();
        }

        $this->info("Importazione iniziata...");

        $rows = [];
        $seen = [];

        foreach ($cities as $city) {

            if (!isset($city['name'], $city['lat'], $city['lon'])) {
                continue;
            }

            // ✅ Normalizza nome
            $name = trim($city['name']);
            $name = preg_replace('/\s+/', ' ', $name);
            $name = trim($name);

            // Chiave per deduplica
            $key = mb_strtolower($name, 'UTF-8');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $rows[] = [
                'name' => $name,
                'latitude' => (float) $city['lat'],
                'longitude' => (float) $city['lon'],
                'country' => 'Italia',
                'country_code' => 'IT',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ✅ Inserimento con UPSERT (non esplode su UNIQUE)
        City::upsert(
            $rows,
            ['name'], // campo unique
            ['latitude', 'longitude', 'updated_at']
        );

        $this->info("Import completato!");
        $this->info("Totale città elaborate: " . count($rows));

        return Command::SUCCESS;
    }
}
