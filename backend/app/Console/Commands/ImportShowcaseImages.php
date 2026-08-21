<?php

namespace App\Console\Commands;

use App\Models\ShowcaseImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportShowcaseImages extends Command
{
    protected $signature = 'showcase:import';
    protected $description = 'Importa nel DB le immagini presenti in storage/app/public/showcase/{testimonials,collabs} non ancora registrate';

    private array $folders = [
        'testimonials' => 'testimonial',
        'collabs' => 'collab',
    ];

    public function handle()
    {
        $disk = Storage::disk('public');
        $imported = 0;

        foreach ($this->folders as $folder => $type) {
            $files = $disk->files("showcase/{$folder}");

            foreach ($files as $path) {
                if (ShowcaseImage::where('image_path', $path)->exists()) {
                    continue;
                }

                $fileName = pathinfo($path, PATHINFO_FILENAME);
                $caption = ucfirst(str_replace(['-', '_'], ' ', $fileName));

                ShowcaseImage::create([
                    'type' => $type,
                    'image_path' => $path,
                    'caption' => $caption,
                ]);

                $imported++;
                $this->info("Importata: {$path}");
            }
        }

        $this->info("Totale immagini importate: {$imported}");

        return Command::SUCCESS;
    }
}
