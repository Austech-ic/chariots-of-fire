<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true, 'is_enabled' => true, 'sort_order' => 0],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'is_default' => false, 'is_enabled' => false, 'sort_order' => 1],
            ['code' => 'yo', 'name' => 'Yoruba', 'native_name' => 'Yorùbá', 'is_default' => false, 'is_enabled' => false, 'sort_order' => 2],
            ['code' => 'ig', 'name' => 'Igbo', 'native_name' => 'Igbo', 'is_default' => false, 'is_enabled' => false, 'sort_order' => 3],
            ['code' => 'ha', 'name' => 'Hausa', 'native_name' => 'Hausa', 'is_default' => false, 'is_enabled' => false, 'sort_order' => 4],
        ];

        foreach ($languages as $language) {
            // Never override choices an administrator has already made.
            Language::firstOrCreate(['code' => $language['code']], $language);
        }
    }
}
