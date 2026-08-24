<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private const PER_PAGE = 25;

    private const TYPES = ['registration', 'login', 'content', 'system', 'error'];

    private const ROLES = ['student', 'teacher', 'admin'];

    private const RETENTION_DAYS = [30, 60, 90, 365];

    /**
     * Paginated, searchable, filterable activity log listing for the admin
     * dashboard's Activity tab. Active (non-archived) logs by default.
     *
     * Supports HTTP conditional requests (ETag/If-None-Match) so a client
     * polling this on an interval — watching for new platform events —
     * gets a cheap 304 with no body when nothing on this exact page/filter
     * combination has actually changed.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'type' => ['nullable', 'string', 'in:'.implode(',', self::TYPES)],
            'role' => ['nullable', 'string', 'in:'.implode(',', self::ROLES)],
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'archived' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = ActivityLog::query()
            ->search($validated['q'] ?? null)
            ->ofType($validated['type'] ?? null)
            ->ofRole($validated['role'] ?? null)
            ->onDate($validated['date'] ?? null)
            ->betweenDates($validated['from'] ?? null, $validated['to'] ?? null);

        $query = ($validated['archived'] ?? false) ? $query->archived() : $query->active();

        $logs = $query->orderByDesc('created_at')->paginate(self::PER_PAGE)->withQueryString();
        $counters = $this->todayCounters();

        // Fingerprinted from what can actually change this exact page's
        // meaning — the id/created_at/archived_at of the rows actually
        // returned, the total match count, and today's counters — not the
        // rendered payload (which includes "X minutes ago" style text that
        // drifts on its own and would defeat 304 caching entirely).
        $rowFingerprint = collect($logs->items())
            ->map(fn (ActivityLog $log) => $log->id.':'.$log->created_at?->timestamp.':'.($log->archived_at?->timestamp ?? 'active'))
            ->implode('|');
        $fingerprint = $rowFingerprint.'#'.$logs->total().'#'.implode(',', $counters);

        $response = response()->json([
            'data' => collect($logs->items())->map(fn (ActivityLog $log) => $this->toPayload($log)),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
            ],
            'counters' => $counters,
        ]);
        $response->setEtag(md5($fingerprint));

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    /**
     * The full set of matching rows (unpaginated, capped) for CSV/PDF
     * export — built client-side so it follows the same jsPDF/SheetJS
     * pattern already used everywhere else on this dashboard.
     */
    public function exportData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'type' => ['nullable', 'string', 'in:'.implode(',', self::TYPES)],
            'role' => ['nullable', 'string', 'in:'.implode(',', self::ROLES)],
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'archived' => 'nullable|boolean',
        ]);

        $query = ActivityLog::query()
            ->search($validated['q'] ?? null)
            ->ofType($validated['type'] ?? null)
            ->ofRole($validated['role'] ?? null)
            ->onDate($validated['date'] ?? null)
            ->betweenDates($validated['from'] ?? null, $validated['to'] ?? null);

        $query = ($validated['archived'] ?? false) ? $query->archived() : $query->active();

        $logs = $query->orderByDesc('created_at')->limit(5000)->get();

        return response()->json([
            'data' => $logs->map(fn (ActivityLog $log) => $this->toPayload($log)),
        ]);
    }

    /**
     * Permanently delete a single log entry. Confirmed client-side before
     * this is ever called; admin-only via the role:admin route middleware.
     */
    public function destroy(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->delete();

        return response()->json(['message' => 'Activity log deleted.']);
    }

    /**
     * Archive (not delete) every active log older than the chosen
     * retention window, so history stays recoverable instead of being
     * permanently wiped in one click.
     */
    public function archiveOld(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'in:'.implode(',', self::RETENTION_DAYS)],
        ]);

        $cutoff = now()->subDays($validated['days']);

        $count = ActivityLog::query()
            ->active()
            ->where('created_at', '<', $cutoff)
            ->update(['archived_at' => now()]);

        return response()->json([
            'message' => "{$count} log(s) older than {$validated['days']} days archived.",
            'count' => $count,
        ]);
    }

    /**
     * Restore a single archived log back to the active timeline.
     */
    public function restore(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->update(['archived_at' => null]);

        return response()->json(['message' => 'Activity log restored.']);
    }

    /**
     * @return array{eventsToday: int, loginsToday: int, errorsToday: int}
     */
    private function todayCounters(): array
    {
        $today = ActivityLog::query()->active()->whereDate('created_at', now()->toDateString());

        return [
            'eventsToday' => (clone $today)->count(),
            'loginsToday' => (clone $today)->where('type', 'login')->count(),
            'errorsToday' => (clone $today)->where('type', 'error')->count(),
        ];
    }

    /**
     * @return array{id: string, type: string, title: string, sub: ?string, badge: ?string, userName: ?string, userRole: ?string, createdAt: string, time: string, archived: bool}
     */
    private function toPayload(ActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'type' => $log->type,
            'title' => $log->title,
            'sub' => $log->sub,
            'badge' => $log->badge,
            'userName' => $log->user_name,
            'userRole' => $log->user_role,
            'createdAt' => $log->created_at?->toIso8601String(),
            'time' => $log->created_at?->diffForHumans(),
            'archived' => $log->archived_at !== null,
        ];
    }
}
