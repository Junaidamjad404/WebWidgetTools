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
                'name' => 'First Sign Up Discount',
                'handle'=> 'First_Sign_Up_Discount',
                'description' => 'A customizable header module for branding and navigation.',
                'image'=> 'images/general_modules/FSD.png',
                'settings' => json_encode($metafieldValue),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Estimated Shipping Date and time',
                'handle' => 'Estimated_Shipping_Date_and_time',
                'description' => 'A customizable footer module for contact information and links.',
                'image'=> 'images/general_modules/images.png',
                'settings' => json_encode($metafieldValue),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Shipping Details',
                'handle' => 'Shipping_Details',
                'description' => 'A customizable sidebar module for showcasing categories and offers.',
                'image' => 'images/general_modules/shipping_details.png',
                'settings' => json_encode($metafieldValue),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('general_modules')->insert($modules);
    }
}
