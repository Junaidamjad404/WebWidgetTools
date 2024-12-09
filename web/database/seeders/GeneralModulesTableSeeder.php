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
        $metafieldValue = [
            'title' => "Custom Widget",
            'content' => "This is a content of the widget",
            'bgColor' =>  '#ffffff',  // Default to white if null
            'padding' =>  '10px',   // Default padding
            'margin' =>  '5px',     // Default margin
            'fontSize' =>  '14px', // Default font size
            'fontWeight' =>  'normal', // Default font weight
            'textColor' =>  '#000000', // Default text color (black)
            'emailPadding' =>  '10px',  // Default padding for email input
            'buttonBgColor' =>  '#000000', // Default button background color (black)
            'buttonFontSize' =>  '14px', // Default button font size
        ];
        $modules = [
            [
                'name' => 'Header Widget',
                'handle'=> 'Header-Widget-1',
                'description' => 'A customizable header module for branding and navigation.',
                'settings' => json_encode($metafieldValue),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Footer Widget',
                'handle' => 'Header-Widget-2',
                'description' => 'A customizable footer module for contact information and links.',
                'settings' => json_encode($metafieldValue),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sidebar Widget',
                'handle' => 'Header-Widget-3',

                'description' => 'A customizable sidebar module for showcasing categories and offers.',
                'settings' => json_encode($metafieldValue),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('general_modules')->insert($modules);
    }
}
