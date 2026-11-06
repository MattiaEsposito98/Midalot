<?php

namespace Database\Seeders;

use App\Models\AvatarItem;
use Illuminate\Database\Seeder;

/**
 * Inserisce gli oggetti del catalogo che non esistono ancora (per key).
 * Gli oggetti gia' presenti non vengono toccati.
 */
class AvatarItemSeeder extends Seeder
{
    public function run(): void
    {
        $rows = require database_path('data/avatar_items.php');
        $existing = AvatarItem::pluck('key')->flip();
        $created = 0;

        foreach ($rows as $i => [$key, $name, $slot, $rarity, $level, $price, $shopMinLevel]) {
            if (isset($existing[$key])) {
                continue;
            }

            AvatarItem::create([
                'key' => $key,
                'name' => $name,
                'slot' => $slot,
                'rarity' => $rarity,
                'unlock_level' => $level,
                'price' => $price,
                'shop_min_level' => $shopMinLevel,
                'sort_order' => $i,
            ]);
            $created++;
        }

        $this->command?->info("Oggetti avatar creati: {$created}");
    }
}
