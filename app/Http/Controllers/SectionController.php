<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\JsonResponse;

class SectionController extends Controller
{
    /**
     * Return all sections as JSON.
     *
     * Public and read-only: used by the registration form (pre-login) and
     * the teacher dashboard. Section mutations go through the
     * ownership-checked Teacher\SectionController routes instead.
     */
    public function index(): JsonResponse
    {
        $sections = Section::with('students')->orderBy('created_at')->get();

        return response()->json([
            'sections' => $sections->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'students' => $s->students->pluck('id')->toArray(),
                'students_count' => $s->students()->count(),
                'avg_progress' => 0, // Placeholder - will be calculated based on actual performance data
                'needs_attention' => 0, // Placeholder - will be calculated based on performance thresholds
            ]),
        ]);
    }
}
