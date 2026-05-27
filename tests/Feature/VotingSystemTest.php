<?php

namespace Tests\Feature;

use App\Enums\PollStatus;
use App\Models\Poll;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotingSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'first_name', 'last_name', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'first_name', 'last_name', 'email'],
                'token',
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create();
        $plainToken = 'test-token-string';
        $user->personalAccessTokens()->create([
            'token' => hash('sha256', $plainToken),
            'last_used' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => $user->email]);
    }

    public function test_unauthenticated_user_cannot_access_me_endpoint(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $plainToken = 'test-token-string';
        $token = $user->personalAccessTokens()->create([
            'token' => hash('sha256', $plainToken),
            'last_used' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->id,
        ]);
    }

    public function test_user_can_create_poll_with_options(): void
    {
        $user = User::factory()->create();
        $plainToken = 'test-token-string';
        $user->personalAccessTokens()->create([
            'token' => hash('sha256', $plainToken),
            'last_used' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
        ])->postJson('/api/polls', [
            'title' => 'What is your favorite color?',
            'options' => ['Red', 'Blue', 'Green'],
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'What is your favorite color?']);

        $poll = Poll::first();
        $this->assertNotNull($poll);
        $this->assertEquals(3, $poll->options()->count());
    }

    public function test_user_can_list_only_their_polls(): void
    {
        $user = User::factory()->create();
        $plainToken = 'test-token-string';
        $user->personalAccessTokens()->create([
            'token' => hash('sha256', $plainToken),
            'last_used' => now(),
        ]);

        $otherUser = User::factory()->create();

        Poll::create([
            'user_id' => $user->id,
            'title' => 'User Poll',
        ]);

        Poll::create([
            'user_id' => $otherUser->id,
            'title' => 'Other User Poll',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
        ])->getJson('/api/polls');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'User Poll'])
            ->assertJsonMissing(['title' => 'Other User Poll']);
    }

    public function test_public_user_can_view_any_poll(): void
    {
        $user = User::factory()->create();
        $poll = Poll::create([
            'user_id' => $user->id,
            'title' => 'Public Poll',
        ]);
        $poll->options()->create(['value' => 'Option A']);

        $response = $this->getJson('/api/polls/'.$poll->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Public Poll']);
    }

    public function test_poll_owner_can_update_poll(): void
    {
        $user = User::factory()->create();
        $plainToken = 'test-token-string';
        $user->personalAccessTokens()->create([
            'token' => hash('sha256', $plainToken),
            'last_used' => now(),
        ]);

        $poll = Poll::create([
            'user_id' => $user->id,
            'title' => 'Original Title',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
        ])->putJson('/api/polls/'.$poll->id, [
            'title' => 'Updated Title',
            'status' => 'closed',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title', 'status' => 'closed']);
    }

    public function test_non_owner_cannot_update_poll(): void
    {
        $user = User::factory()->create();
        $plainToken = 'test-token-string';
        $user->personalAccessTokens()->create([
            'token' => hash('sha256', $plainToken),
            'last_used' => now(),
        ]);

        $otherUser = User::factory()->create();
        $poll = Poll::create([
            'user_id' => $otherUser->id,
            'title' => 'Other User Poll',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
        ])->putJson('/api/polls/'.$poll->id, [
            'title' => 'Attempted Update',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_cast_vote(): void
    {
        $user = User::factory()->create();
        $poll = Poll::create([
            'user_id' => $user->id,
            'title' => 'Active Poll',
            'status' => PollStatus::OPEN,
        ]);
        $option = $poll->options()->create(['value' => 'Option A']);

        $response = $this->postJson('/api/votes', [
            'poll_uuid' => $poll->id,
            'option_id' => $option->id,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(1, Vote::count());
    }

    public function test_user_cannot_vote_on_closed_poll(): void
    {
        $user = User::factory()->create();
        $poll = Poll::create([
            'user_id' => $user->id,
            'title' => 'Closed Poll',
            'status' => PollStatus::CLOSED,
        ]);
        $option = $poll->options()->create(['value' => 'Option A']);

        $response = $this->postJson('/api/votes', [
            'poll_uuid' => $poll->id,
            'option_id' => $option->id,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'This poll is closed.']);
    }

    public function test_user_cannot_vote_twice_on_same_poll_from_same_ip(): void
    {
        $user = User::factory()->create();
        $poll = Poll::create([
            'user_id' => $user->id,
            'title' => 'Poll',
            'status' => PollStatus::OPEN,
        ]);
        $optionA = $poll->options()->create(['value' => 'Option A']);
        $optionB = $poll->options()->create(['value' => 'Option B']);

        // First vote
        $response1 = $this->postJson('/api/votes', [
            'poll_uuid' => $poll->id,
            'option_id' => $optionA->id,
        ]);
        $response1->assertStatus(201);

        // Second vote (should fail)
        $response2 = $this->postJson('/api/votes', [
            'poll_uuid' => $poll->id,
            'option_id' => $optionB->id,
        ]);
        $response2->assertStatus(409)
            ->assertJson(['message' => 'You have already voted on this poll.']);
    }
}
