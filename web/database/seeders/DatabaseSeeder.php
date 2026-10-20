<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun demo untuk login dashboard. Password otomatis di-hash (bcrypt) oleh cast 'hashed' di model User.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kehutanan.test'],
            ['name' => 'Admin Kelompok 3', 'password' => 'hutan2026'],
        );
    }
}
