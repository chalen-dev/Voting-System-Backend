<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            ->get()
            ->each(fn($option) => $this->appendImageUrl($option));

        return response()->json($options);
    }

    /**
     * Add an option to a poll with optional image. Poll owner only.
     * Accepts Multipart/Form-Data.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'poll_uuid' => 'required|uuid|exists:polls,id',
            'value' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
        ]);

        $poll = Poll::findOrFail($validated['poll_uuid']);

        if ($poll->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('options', 'public');
        }

        $option = $poll->options()->create([
            'value' => $validated['value'],
            'image_path' => $imagePath,
        ]);

        return response()->json($this->appendImageUrl($option), 201);
    }

    /**
     * Display a single option with its vote count.
     */
    public function show(Option $option): JsonResponse
    {
        $option->loadCount('votes');

        return response()->json($this->appendImageUrl($option));
    }

    /**
     * Update an option's value and/or image. Poll owner only.
     * Accepts Multipart/Form-Data.
     */
    public function update(Request $request, Option $option): JsonResponse
    {
        if ($option->poll->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'value' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'remove_image' => 'sometimes|boolean',
        ]);

        // Remove image if explicitly requested
        if ($request->boolean('remove_image') && $option->image_path) {
            Storage::disk('public')->delete($option->image_path);
            $option->image_path = null;
        }

        // Replace image if a new one is uploaded
        if ($request->hasFile('image')) {
            // Delete old image first
            if ($option->image_path) {
                Storage::disk('public')->delete($option->image_path);
            }
            $option->image_path = $request->file('image')->store('options', 'public');
        }

        if (isset($validated['value'])) {
            $option->value = $validated['value'];
        }

        $option->save();

        return response()->json($this->appendImageUrl($option));
    }

    /**
     * Delete an option and its image. Poll owner only.
     */
    public function destroy(Option $option): JsonResponse
    {
        if ($option->poll->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Delete image file from storage
        if ($option->image_path) {
            Storage::disk('public')->delete($option->image_path);
        }

        $option->delete();

        return response()->json(['message' => 'Option deleted successfully.']);
    }

    /**
     * Append a full public image_url to the option object.
     */
    private function appendImageUrl(Option $option): Option
    {
        $option->image_url = $option->image_path
            ? asset('storage/' . $option->image_path)
            : null;

        return $option;
    }
}