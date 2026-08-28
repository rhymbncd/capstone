<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ModuleStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModuleController extends Controller
{
    /**
     * The fixed curriculum PDFs shown in the module reader, keyed by topic.
     * A student may only fetch a file that is on this list — no arbitrary
     * bucket paths.
     *
     * @var array<string, string>
     */
    private const CURRICULUM_FILES = [
        'ari' => 'Arithmetic Sequence.pdf',
        'geo' => 'Geometric Sequence.pdf',
        'har' => 'Harmonic Sequence.pdf',
        'fib' => 'Fibonacci Sequence.pdf',
        'fin' => 'Finite and Infinite Sequence.pdf',
        'div' => 'Division of Polynomials.pdf',
        'rem' => 'The Remainder and Factor Theorem.pdf',
        'poly' => 'Polynomial Equation.pdf',
        'rat' => 'Rational Functions.pdf',
        'rad' => 'Radical Equations.pdf',
        'exp' => 'Exponential Functions.pdf',
        'log' => 'Logarithmic Functions.pdf',
    ];

    /**
     * Approved teacher-submitted modules, for the student "materials" list.
     */
    public function index(): JsonResponse
    {
        $modules = ModuleStatus::where('status', 'approved')
            ->whereNotNull('module_topic')
            ->get(['id', 'module_title', 'module_topic', 'module_desc'])
            ->map(fn (ModuleStatus $m) => [
                'id' => $m->id,
                'title' => $m->module_title,
                'topic' => $m->module_topic,
                'desc' => $m->module_desc,
            ]);

        return response()->json(['modules' => $modules]);
    }

    /**
     * Redirect to a short-lived signed URL for one curriculum PDF, chosen
     * by either its topic key (?topic=) or its exact filename (?name=).
     * Both are checked against the fixed whitelist — no arbitrary paths.
     */
    public function file(Request $request): RedirectResponse
    {
        $byTopic = self::CURRICULUM_FILES[$request->query('topic')] ?? null;
        $name = $request->query('name');
        $byName = $name && in_array($name, self::CURRICULUM_FILES, true) ? $name : null;

        $filename = $byTopic ?? $byName;
        abort_if($filename === null, 404);

        return redirect()->away(
            Storage::disk('supabase_materials')->temporaryUrl($filename, now()->addMinutes(10)),
        );
    }

    /**
     * Redirect to a short-lived signed URL for an approved teacher module.
     */
    public function download(ModuleStatus $moduleStatus): RedirectResponse
    {
        abort_unless($moduleStatus->status === 'approved', 404);

        return redirect()->away(
            Storage::disk('supabase')->temporaryUrl($moduleStatus->file_name, now()->addMinutes(10)),
        );
    }
}
