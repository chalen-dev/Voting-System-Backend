<?php

namespace Database\Seeders;

use App\Models\Poll;
use App\Models\User;
use App\Enums\PollStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PollSeeder extends Seeder
{
    public function run(): void
    {
        // Find the first user (likely created by your UserSeeder)
        $user = User::first();

        if (!$user) {
            $this->command->error("No user found! Run UserSeeder first.");
            return;
        }

        $pollData = [
            [
                'title' => 'What is your favorite Javascript Framework?',
                'status' => PollStatus::OPEN,
                'options' => ['React', 'Vue', 'Angular', 'Svelte'],
            ],
            [
                'title' => 'Preferred Backend Language',
                'status' => PollStatus::OPEN,
                'options' => ['PHP (Laravel)', 'Python (Django)', 'Node.js', 'Go'],
            ],
            [
                'title' => 'Best Coffee Type',
                'status' => PollStatus::CLOSED,
                'options' => ['Espresso', 'Latte', 'Cappuccino', 'Americano'],
            ],
            [
                'title' => 'Remote Work Preference',
                'status' => PollStatus::OPEN,
                'options' => ['Full Remote', 'Hybrid', 'Office Only'],
            ],
        ];

        foreach ($pollData as $data) {
            $poll = $user->polls()->create([
                'title' => $data['title'],
                'status' => $data['status'],
                'start_time' => Carbon::now(),
                'end_time' => Carbon::now()->addDays(7),
            ]);

            foreach ($data['options'] as $optionValue) {
                $poll->options()->create(['value' => $optionValue]);
            }
        }

        // Create 10 more random "test" polls to test your new pagination
        for ($i = 1; $i <= 10; $i++) {
            $poll = $user->polls()->create([
                'title' => "Extra Test Poll #{$i}",
                'status' => PollStatus::OPEN,
                'start_time' => Carbon::now(),
            ]);
            $poll->options()->create(['value' => 'Sample Option']);
        }
    }
}
