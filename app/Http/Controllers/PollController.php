<?php

namespace App\Http\Controllers;

use App\Enums\PollStatus;
use App\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ->get()
            ->each(function ($poll) {
                $poll->options->each(fn($option) => $this->appendImageUrl($option));
            });

        return response()->json($polls);
    }

    /**
     * Create a new poll with options.
     * Accepts Multipart/Form-Data to support per-option image uploads.
     *
     * Expected body:
     *   title          string
     *   options[]      string[]         (at least 2)
     *   images[]       file[]|null      (optional, index-matched to options[])
     *   start_time     datetime|null
     *   end_time       datetime|null
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string|max:255',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ]);

        $poll = DB::transaction(function () use ($validated, $request) {
            $poll = Auth::user()->polls()->create([
                'title' => $validated['title'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
            ]);

            foreach ($validated['options'] as $index => $optionValue) {
                $imagePath = null;

                // Check if an image was uploaded for this specific option index
                if ($request->hasFile("images.{$index}")) {
                    $imagePath = $request->file("images.{$index}")->store('options', 'public');
                }

                $poll->options()->create([
                    'value' => $optionValue,
                    'image_path' => $imagePath,
                ]);
            }

            return $poll->load('options');
        });

        // Append image_url to each option
        $poll->options->each(fn($option) => $this->appendImageUrl($option));

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

        $poll->options->each(fn($option) => $this->appendImageUrl($option));

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
     * Also deletes any stored option images.
     */
    public function destroy(Poll $poll): JsonResponse
    {
        if ($poll->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Delete all option images before deleting the poll
        foreach ($poll->options as $option) {
            if ($option->image_path) {
                Storage::disk('public')->delete($option->image_path);
            }
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

        // Load options to delete their images first
        $polls = Auth::user()->polls()->with('options')->whereIn('id', $validated['ids'])->get();

        foreach ($polls as $poll) {
            foreach ($poll->options as $option) {
                if ($option->image_path) {
                    Storage::disk('public')->delete($option->image_path);
                }
            }
        }

        $deleted = Auth::user()
            ->polls()
            ->whereIn('id', $validated['ids'])
            ->delete();

        return response()->json([
            'message' => "{$deleted} poll(s) deleted successfully.",
            'deleted' => $deleted,
        ]);
    }

    /**
     * Append a full public image_url to an option object.
     */
    private function appendImageUrl($option): void
    {
        $option->image_url = $option->image_path
            ? asset('storage/' . $option->image_path)
            : null;
    }
}