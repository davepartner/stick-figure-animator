<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed system settings
        $this->call(SystemSettingsSeeder::class);

        // Create admin user
        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@stickfigure.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'credits' => 10000,
        ]);

        // Create test user
        \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'user@stickfigure.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'credits' => 100,
        ]);
    }
}
