<?php
// database/seeders/AdminSettingSeeder.php

namespace Database\Seeders;

use App\Models\AdminSetting;
use Illuminate\Database\Seeder;

class AdminSettingSeeder extends Seeder
{
    public function run(): void
    {
        $count = AdminSetting::seedDefaults();

        $this->command->info("✅ {$count} تنظیمات پیش‌فرض ساخته شد.");
    }
}