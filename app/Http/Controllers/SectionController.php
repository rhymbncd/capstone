<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SectionController extends Controller
{
    /**
     * Return the list of sections (id + name only) as JSON.
     *
     * Public and read-only: the only consumer is the pre-login registration
     * form's section picker. The authenticated teacher dashboard uses the
     * ownership-scoped Teacher\SectionController@list instead. Deliberately
     * exposes no roster or counts — an unauthenticated caller has no
     * business enumerating who is enrolled where. Section mutations bust
     * this cache via Teacher\SectionController::forgetSectionsCache().
     */
    public function index(): JsonResponse
    {
        $sections = Cache::remember(
            'sections.public_list',
            now()->addHours(6),
            fn () => Section::orderBy('created_at')->get(['id', 'name'])
        );

        return response()->json([
            'sections' => $sections->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
        ]);
    }
}
