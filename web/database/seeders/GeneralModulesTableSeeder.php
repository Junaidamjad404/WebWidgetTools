<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneralModulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $modules = [
            [
                'name' => 'Header Widget',
                'handle'=> 'Header-Widget-1',
                'description' => 'A customizable header module for branding and navigation.',
                'settings' => json_encode([
                    'font_color' => '#000000',
                    'background_color' => '#ffffff',
                    'text' => 'Welcome to our shop!'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Footer Widget',
                'handle' => 'Header-Widget-2',
                'description' => 'A customizable footer module for contact information and links.',
                'settings' => json_encode([
                    'font_color' => '#ffffff',
                    'background_color' => '#333333',
                    'text' => 'Thank you for visiting our shop.'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sidebar Widget',
                'handle' => 'Header-Widget-3',

                'description' => 'A customizable sidebar module for showcasing categories and offers.',
                'settings' => json_encode([
                    'font_color' => '#000000',
                    'background_color' => '#f4f4f4',
                    'text' => 'Explore our categories.'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('general_modules')->insert($modules);

    }
}
