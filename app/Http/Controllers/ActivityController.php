<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    private const TYPES = ['registration', 'login', 'content', 'system', 'error'];

    /**
     * Record a client-side observability event (report downloaded, quiz
     * published, settings saved, …). The actor is always taken from the
     * session — the request cannot claim to be someone else.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(self::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'sub' => ['nullable', 'string', 'max:1000'],
            'badge' => ['nullable', 'string', 'max:50'],
        ]);

        ActivityLog::record(
            $validated['type'],
            $validated['title'],
            $validated['sub'] ?? null,
            $validated['badge'] ?? null,
            user: $request->user(),
        );

        return response()->json(['logged' => true]);
    }
}
