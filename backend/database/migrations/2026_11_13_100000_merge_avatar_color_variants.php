<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Oggetti che differivano solo per colore diventano un unico oggetto con
 * tavolozza (config/avatar.php -> palettes). I vecchi oggetti restano nel
 * database ma disattivati: chi li possedeva ha il colore sbloccato per sempre
 * e gli avatar salvati vengono convertiti in automatico (AvatarService).
 */
return new class extends Migration
{
    private const RENAME = [
        'maglietta_bianca' => ['Maglietta', 'Maglietta bianca'],
        'cappellino' => ['Cappellino', 'Cappellino rosso'],
        'gonna_rossa' => ['Gonna', 'Gonna rossa'],
    ];

    private const RETIRED = [
        'maglietta_rossa', 'maglietta_blu', 'maglietta_verde', 'maglietta_gialla', 'maglietta_rosa', 'maglietta_nera',
        'jeans_neri', 'sneakers_nere', 'cappellino_blu', 'cappellino_verde', 'gonna_blu',
    ];

    public function up(): void
    {
        foreach (self::RENAME as $key => [$new]) {
            DB::table('avatar_items')->where('key', $key)->update(['name' => $new, 'updated_at' => now()]);
        }

        DB::table('avatar_items')->whereIn('key', self::RETIRED)->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        foreach (self::RENAME as $key => [, $old]) {
            DB::table('avatar_items')->where('key', $key)->update(['name' => $old, 'updated_at' => now()]);
        }

        DB::table('avatar_items')->whereIn('key', self::RETIRED)->update(['is_active' => true, 'updated_at' => now()]);
    }
};
