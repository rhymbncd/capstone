<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ModuleStatus;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        'rem' => 'The Remainder Theorem and Factor Theorem.pdf',
        'poly' =>'Polynomial Equations.pdf',
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
     * Stream one curriculum PDF as a download, chosen by topic key
     * (?topic=) or exact filename (?name=). Both are checked against the
     * fixed whitelist — no arbitrary bucket paths. The in-page reader also
     * fetches this endpoint for the raw bytes.
     */
    public function file(Request $request): StreamedResponse
    {
        $byTopic = self::CURRICULUM_FILES[$request->query('topic')] ?? null;
        $name = $request->query('name');
        $byName = $name && in_array($name, self::CURRICULUM_FILES, true) ? $name : null;

        $filename = $byTopic ?? $byName;
        abort_if($filename === null, 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('supabase_materials');

        // Clean 404 (and a log entry) instead of a 500 when the PDF is
        // missing from the bucket or has a different name.
        if (! $disk->exists($filename)) {
            Log::warning('Curriculum PDF missing in bucket', ['file' => $filename]);
            abort(404, 'This module file is not available yet.');
        }

        return $disk->download($filename);
    }

    /**
     * Stream an approved teacher module as a download.
     */
    public function download(ModuleStatus $moduleStatus): StreamedResponse
    {
        abort_unless($moduleStatus->status === 'approved', 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('supabase');

        if (! $disk->exists($moduleStatus->file_name)) {
            Log::warning('Teacher module missing in bucket', [
                'module_id' => $moduleStatus->id,
                'file' => $moduleStatus->file_name,
            ]);
            abort(404, 'This module file is not available.');
        }

        return $disk->download(
            $moduleStatus->file_name,
            ($moduleStatus->module_title ?: 'module').'.pdf',
        );
    }
}