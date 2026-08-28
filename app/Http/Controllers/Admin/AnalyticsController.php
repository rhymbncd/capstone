<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentProgress;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    /**
     * Every student_progress row, platform-wide, for the admin Analytics
     * tab (DAU, average pre/post scores, completions, per-module rates).
     * Admin-only via the role:admin route middleware; the browser used to
     * read this straight from Supabase with the public anon key.
     */
    public function progress(): JsonResponse
    {
        return response()->json([
            'progress' => StudentProgress::query()
                ->get(['session_id', 'topic_key', 'phase', 'score', 'total', 'passed', 'created_at']),
        ]);
    }
}
