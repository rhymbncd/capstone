<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SectionController extends Controller
{
    /**
     * Return all sections as JSON.
     *
     * Public and read-only: used by the registration form (pre-login) and
     * the teacher dashboard. Section mutations go through the
     * ownership-checked Teacher\SectionController routes instead. Cached
     * briefly since it includes each section's student roster, which can
     * also change via student registration/approval and not just section
     * CRUD — a short TTL bounds that staleness instead of relying solely on
     * Teacher\SectionController's cache invalidation.
     */
    public function index(): JsonResponse
    {
        $sections = Cache::remember(
            'sections.with_students',
            now()->addMinutes(5),
            fn () => Section::with('students')->orderBy('created_at')->get()
        );

        return response()->json([
            'sections' => $sections->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'students' => $s->students->pluck('id')->toArray(),
                'students_count' => $s->students->count(),
                'avg_progress' => 0, // Placeholder - will be calculated based on actual performance data
                'needs_attention' => 0, // Placeholder - will be calculated based on performance thresholds
            ]),
        ]);
    }
}
