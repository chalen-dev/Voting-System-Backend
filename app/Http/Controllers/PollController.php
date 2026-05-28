<?php

namespace App\Http\Controllers;

use App\Enums\PollStatus;
use App\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PollController extends Controller
{
    /**
     * List all polls for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $polls = Auth::user()
            ->polls()
            ->with([
                'options' => function ($query) {
                    $query->withCount('votes');
                }
            ])
            ->latest()
            ->get();

        return response()->json($polls);
    }

    /**
     * Create a new poll with options.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string|max:255',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ]);

        $poll = DB::transaction(function () use ($validated) {
            $poll = Auth::user()->polls()->create([
                'title' => $validated['title'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
            ]);

            foreach ($validated['options'] as $optionValue) {
                $poll->options()->create(['value' => $optionValue]);
            }

            return $poll->load('options');
        });

        return response()->json($poll, 201);
    }

    /**
     * Display a single poll with its options and vote counts.
     */
    public function show(Poll $poll): JsonResponse
    {
        $poll->load([
            'options' => function ($query) {
                $query->withCount('votes');
            }
        ]);

        return response()->json($poll);
    }

    /**
     * Update a poll (title, status, start_time, end_time). Owner only.
     */
    public function update(Request $request, Poll $poll): JsonResponse
    {
        if ($poll->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'status' => ['sometimes', 'required', Rule::enum(PollStatus::class)],
            'start_time' => 'sometimes|nullable|date',
            'end_time' => 'sometimes|nullable|date|after_or_equal:start_time',
        ]);

        $poll->update($validated);

        return response()->json($poll);
    }

    /**
     * Delete a poll. Owner only. Cascades to options and votes.
     */
    public function destroy(Poll $poll): JsonResponse
    {
        if ($poll->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $poll->delete();

        return response()->json(['message' => 'Poll deleted successfully.']);
    }

    /**
     * Bulk update the status of multiple polls. Owner only.
     * 
     * POST /polls/bulk-status
     * Body: { "ids": ["uuid1", "uuid2"], "status": "open" }
     */
    public function bulkStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|uuid|exists:polls,id',
            'status' => ['required', Rule::enum(PollStatus::class)],
        ]);

        $updated = Auth::user()
            ->polls()
            ->whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'message' => "{$updated} poll(s) updated to {$validated['status']}.",
            'updated' => $updated,
        ]);
    }

    /**
     * Bulk delete multiple polls. Owner only. Cascades to options and votes.
     * 
     * DELETE /polls/bulk-destroy
     * Body: { "ids": ["uuid1", "uuid2"] }
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|uuid|exists:polls,id',
        ]);

        $deleted = Auth::user()
            ->polls()
            ->whereIn('id', $validated['ids'])
            ->delete();

        return response()->json([
            'message' => "{$deleted} poll(s) deleted successfully.",
            'deleted' => $deleted,
        ]);
    }
}