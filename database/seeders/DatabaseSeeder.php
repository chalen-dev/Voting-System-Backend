<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Nuke the options folder and all its images
        Storage::disk('public')->deleteDirectory('options');

        // User::factory(10)->create();

        $this->call([
            UserSeeder::class,
            PollSeeder::class
        ]);
    }
}
