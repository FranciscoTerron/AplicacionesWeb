<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Los usuarios y el resto de los datos viven en Firestore (no hay motor
     * SQL): se crean desde el panel admin o vía la API. No hay nada que seedear.
     */
    public function run(): void
    {
        //
    }
}
