<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SchoolYear;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin if not exists
        if (!User::where('username', 'admin')->exists()) {
            User::create([
                'name'     => 'System Administrator',
                'username' => 'admin',
                'email'    => 'admin@smcs.edu.ph',
                'password' => bcrypt('admin123'),
                'role'     => 'admin',
                'status'   => 'active',
            ]);
        }

        // Create default school year if not exists
        if (!SchoolYear::where('year_label', '2026-2027')->exists()) {
            SchoolYear::create([
                'year_label' => '2026-2027',
                'year'       => '2026-2027',
                'start_date' => '2026-06-01',
                'end_date'   => '2027-03-31',
                'is_active'  => true,
                'status'     => 'active',
            ]);
        }
    }
}
