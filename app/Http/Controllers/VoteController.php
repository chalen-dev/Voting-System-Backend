<?php

namespace App\Http\Controllers;

use App\Enums\PollStatus;
use App\Models\Poll;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    /**
     * List votes for a given poll.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'poll_uuid' => 'required|uuid|exists:polls,id',
        ]);

        $votes = Vote::where('poll_uuid', $request->query('poll_uuid'))
            ->with('option')
            ->get();

        return response()->json($votes);
    }

    /**
     * Cast a vote on a poll. One vote per IP per poll.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'poll_uuid' => 'required|uuid|exists:polls,id',
            'option_id' => 'required|integer|exists:options,id',
        ]);

        $poll = Poll::findOrFail($validated['poll_uuid']);

        if ($poll->status !== PollStatus::OPEN) {
            return response()->json(['message' => 'This poll is closed.'], 422);
        }

        $option = $poll->options()->where('id', $validated['option_id'])->first();

        if (! $option) {
            return response()->json(['message' => 'Option does not belong to this poll.'], 422);
        }

        $ipHash = hash('sha256', $request->ip());

        $existingVote = Vote::where('poll_uuid', $validated['poll_uuid'])
            ->where('ip_hash', $ipHash)
            ->first();

        if ($existingVote) {
            return response()->json(['message' => 'You have already voted on this poll.'], 409);
        }

        $vote = Vote::create([
            'poll_uuid' => $validated['poll_uuid'],
            'option_id' => $validated['option_id'],
            'ip_hash' => $ipHash,
        ]);

        return response()->json($vote, 201);
    }

    /**
     * Display a single vote.
     */
    public function show(Vote $vote): JsonResponse
    {
        $vote->load('option');

        return response()->json($vote);
    }

    /**
     * Remove a vote.
     */
    public function destroy(Vote $vote): JsonResponse
    {
        $vote->delete();

        return response()->json(['message' => 'Vote removed successfully.']);
    }
}
