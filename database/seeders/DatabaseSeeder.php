<?php

namespace Database\Seeders;

use App\Models\Printer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'damiancompany@damiancompany.com.pe'], [
            'name' => 'Gerencia DAMIAN',
            'password' => 'somosDamian!1',
            'role' => 'gerencia',
            'is_active' => true,
            'credential_expires_at' => null,
        ]);
    }
}
