<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\GeneralModulesTableSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(GeneralModulesTableSeeder::class);

    }
}
