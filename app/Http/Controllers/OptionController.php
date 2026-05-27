<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionController extends Controller
{
    /**
     * List options for a given poll.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'poll_uuid' => 'required|uuid|exists:polls,id',
        ]);

        $options = Option::where('poll_uuid', $request->query('poll_uuid'))
            ->withCount('votes')
            ->get();

        return response()->json($options);
    }

    /**
     * Add an option to a poll. Poll owner only.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'poll_uuid' => 'required|uuid|exists:polls,id',
            'value' => 'required|string|max:255',
        ]);

        $poll = Poll::findOrFail($validated['poll_uuid']);

        if ($poll->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $option = $poll->options()->create(['value' => $validated['value']]);

        return response()->json($option, 201);
    }

    /**
     * Display a single option with its vote count.
     */
    public function show(Option $option): JsonResponse
    {
        $option->loadCount('votes');

        return response()->json($option);
    }

    /**
     * Update an option's value. Poll owner only.
     */
    public function update(Request $request, Option $option): JsonResponse
    {
        if ($option->poll->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'value' => 'required|string|max:255',
        ]);

        $option->update($validated);

        return response()->json($option);
    }

    /**
     * Delete an option. Poll owner only.
     */
    public function destroy(Option $option): JsonResponse
    {
        if ($option->poll->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $option->delete();

        return response()->json(['message' => 'Option deleted successfully.']);
    }
}
