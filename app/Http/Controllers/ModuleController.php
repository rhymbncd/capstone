<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ModuleStatus;
use App\Services\ModuleLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Teacher + admin access to the shared module library (Supabase Storage +
 * module_status). Routes are behind `auth`; every action re-checks the
 * caller is staff, and status changes require an admin.
 */
class ModuleController extends Controller
{
    public function __construct(private readonly ModuleLibrary $library) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeStaff($request);

        return response()->json(['modules' => $this->library->list()]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeStaff($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,png,jpg,jpeg'],
            'module_title' => ['required', 'string', 'max:255'],
            'module_desc' => ['nullable', 'string', 'max:2000'],
            'module_topic' => ['nullable', 'string', 'max:255'],
        ]);

        $module = $this->library->upload($request->file('file'), $validated);

        ActivityLog::record('content', 'Module Uploaded', "\"{$module->module_title}\" submitted for review", user: $request->user());

        return response()->json(['module' => $module], 201);
    }

    public function update(Request $request, ModuleStatus $moduleStatus): JsonResponse
    {
        $this->authorizeStaff($request);

        $validated = $request->validate([
            'file' => ['sometimes', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,png,jpg,jpeg'],
            'module_title' => ['required', 'string', 'max:255'],
            'module_desc' => ['nullable', 'string', 'max:2000'],
            'module_topic' => ['nullable', 'string', 'max:255'],
        ]);

        $module = $this->library->update($moduleStatus, $validated, $request->file('file'));

        ActivityLog::record('content', 'Module Updated', "\"{$module->module_title}\" was edited", user: $request->user());

        return response()->json(['module' => $module]);
    }

    public function updateTopic(Request $request, ModuleStatus $moduleStatus): JsonResponse
    {
        $this->authorizeStaff($request);

        $validated = $request->validate([
            'module_topic' => ['required', 'string', 'max:255'],
        ]);

        $moduleStatus->update($validated);

        return response()->json(['module' => $moduleStatus]);
    }

    public function updateStatus(Request $request, ModuleStatus $moduleStatus): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
        ]);

        $moduleStatus->update([
            'status' => $validated['status'],
            'reviewed_at' => $validated['status'] === 'pending' ? null : now(),
        ]);

        $labels = ['approved' => 'Material Approved', 'rejected' => 'Material Rejected', 'pending' => 'Status Reset'];
        ActivityLog::record('content', $labels[$validated['status']], "\"{$moduleStatus->module_title}\"", user: $request->user());

        return response()->json(['module' => $moduleStatus]);
    }

    public function destroy(Request $request, ModuleStatus $moduleStatus): JsonResponse
    {
        $this->authorizeStaff($request);

        $title = $moduleStatus->module_title;
        $this->library->delete($moduleStatus);

        ActivityLog::record('content', 'Module Deleted', "\"{$title}\" permanently deleted", user: $request->user());

        return response()->json(['deleted' => true]);
    }

    public function file(Request $request, ModuleStatus $moduleStatus): RedirectResponse
    {
        $this->authorizeStaff($request);

        return redirect()->away(
            Storage::disk('supabase')->temporaryUrl($moduleStatus->file_name, now()->addMinutes(10)),
        );
    }

    private function authorizeStaff(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['teacher', 'admin'], true), 403);
    }
}
