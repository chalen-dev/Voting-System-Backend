<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create a specific test user
        User::updateOrCreate(
            ['email' => 'test@example.com'], // Prevents duplicates if you run it twice
            [
                'first_name' => 'Test',
                'last_name'  => 'User',
                'password'   => Hash::make('test'), // Use this to log in!
            ]
        );

        $this->command->info('Test user created: test@example.com | password: test');
    }
}
