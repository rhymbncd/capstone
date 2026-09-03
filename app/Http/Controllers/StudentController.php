<?php

namespace App\Http\Controllers;

use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /**
     * The real curriculum topics tracked in student_progress.topic_key,
     * matching TOPIC_ORDER in resources/views/dashboard/module.blade.php.
     *
     * @var list<string>
     */
    /**
     * Bumped whenever the shape or derivation of this endpoint's payload
     * changes (e.g. new status rules) so already-cached client copies are
     * invalidated instead of being served stale behind a 304.
     */
    private const PAYLOAD_VERSION = '2025-09-03-not-started-status';

    private const CURRICULUM_TOPICS = [
        'ari', 'geo', 'har', 'fib', 'fin',
        'div', 'rem', 'poly',
        'rat', 'rad', 'exp', 'log',
    ];

    /**
     * Curriculum topics grouped by module, matching MODULE_TOPICS in
     * resources/js/dashboard/student_dashboard.js.
     *
     * @var array<string, list<string>>
     */
    private const MODULE_GROUPS = [
        'Module 1: Sequences and Series' => ['ari', 'geo', 'har', 'fib', 'fin'],
        'Module 2: Polynomials' => ['div', 'rem', 'poly'],
        'Module 3: Advanced Equations' => ['rat', 'rad', 'exp', 'log'],
    ];

    /**
     * Get all students enrolled in the authenticated teacher's sections,
     * with real completion progress computed from student_progress.
     *
     * Supports HTTP conditional requests (ETag/If-None-Match) so a client
     * polling this on an interval — e.g. a teacher watching the roster live
     * during class — gets a cheap 304 with no body when nothing has
     * actually changed.
     */
    public function getTeacherStudents(Request $request): JsonResponse
    {
        $teacher = Auth::user();

        if (! $teacher || $teacher->role !== 'teacher') {
            return response()->json(['students' => []]);
        }

        $students = User::where('role', 'student')
            ->whereHas('section', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->with('section:id,name')
            ->get(['id', 'name', 'email', 'student_id', 'section_id', 'approval_status', 'created_at', 'updated_at']);

        $studentIds = $students->pluck('id')->map(fn ($id) => (string) $id)->all();

        $allProgressRows = StudentProgress::query()
            ->whereIn('session_id', $studentIds)
            ->whereIn('phase', ['pre', 'post'])
            ->whereIn('topic_key', self::CURRICULUM_TOPICS)
            ->get(['session_id', 'topic_key', 'phase', 'score', 'total', 'updated_at']);

        // Fingerprinted from what can actually change the response's
        // meaning — roster membership/approval + the most recent progress
        // write — not the rendered payload (which includes "X minutes ago"
        // style text that drifts on its own and would defeat 304 entirely).
        $rosterFingerprint = $students
            ->map(fn (User $s) => $s->id.':'.$s->approval_status.':'.$s->updated_at->timestamp)
            ->implode('|');
        $progressFingerprint = $allProgressRows->count().':'.optional($allProgressRows->max('updated_at'))?->timestamp;

        $progressRows = $allProgressRows->groupBy('session_id');

        $totalTopics = count(self::CURRICULUM_TOPICS);

        $studentData = $students->map(function ($student) use ($progressRows, $totalTopics) {
            $rows = $progressRows->get((string) $student->id, collect());
            $postRows = $rows->where('phase', 'post');

            $completed = $postRows->pluck('topic_key')->unique()->count();
            $progress = (int) round(($completed / $totalTopics) * 100);

            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'studentId' => $student->student_id,
                'section_id' => $student->section_id,
                'status' => $this->getStudentStatus($student, $progress, $rows->isNotEmpty()),
                'progress' => $progress,
                'avgPre' => $this->averageScorePercent($rows->where('phase', 'pre')),
                'avgPost' => $this->averageScorePercent($postRows),
                'lastActive' => $student->updated_at->diffForHumans(),
            ];
        });

        $response = response()->json([
            'students' => $studentData,
            'subjectCompletion' => $this->subjectCompletion($allProgressRows, $students->count()),
        ]);
        $response->setEtag(md5(self::PAYLOAD_VERSION.'#'.$rosterFingerprint.'#'.$progressFingerprint));

        // Force the browser to revalidate with If-None-Match on every poll
        // rather than serving a heuristically "fresh" copy from its HTTP
        // cache — otherwise a payload change with an unchanged roster (e.g.
        // new status rules) can keep showing stale data until something
        // else moves.
        $response->headers->set('Cache-Control', 'no-cache, private');

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    /**
     * Per-module completion rate across this teacher's own students: what
     * fraction of (student, topic) pairs in each module have a post-test
     * result, out of every pair that's possible for this class.
     *
     * @return list<array{label: string, pct: int}>
     */
    private function subjectCompletion(Collection $progressRows, int $studentCount): array
    {
        $postRows = $progressRows->where('phase', 'post');

        return collect(self::MODULE_GROUPS)->map(function (array $topics, string $label) use ($postRows, $studentCount) {
            $completedPairs = $postRows
                ->whereIn('topic_key', $topics)
                ->map(fn ($row) => $row->session_id.':'.$row->topic_key)
                ->unique()
                ->count();

            $possiblePairs = $studentCount * count($topics);
            $pct = $possiblePairs > 0 ? (int) round(($completedPairs / $possiblePairs) * 100) : 0;

            return ['label' => $label, 'pct' => $pct];
        })->values()->all();
    }

    /**
     * Average score, as a whole-number percentage, across a set of
     * student_progress rows. Returns null when there's nothing to average
     * (an honest "no data" rather than a fabricated 0).
     */
    private function averageScorePercent(Collection $rows): ?int
    {
        $scored = $rows->filter(fn ($row) => $row->total > 0);

        if ($scored->isEmpty()) {
            return null;
        }

        return (int) round($scored->avg(fn ($row) => ($row->score / $row->total) * 100));
    }

    /**
     * Determine student status based on approval status and real progress.
     *
     * A freshly approved student who has not attempted a single pre- or
     * post-test yet is "Not Started" rather than "Needs Help" — there is
     * nothing to be alarmed about until they've actually engaged.
     */
    private function getStudentStatus(User $student, int $progress, bool $hasActivity): string
    {
        if ($student->approval_status !== 'approved') {
            return 'Pending';
        }

        if (! $hasActivity) {
            return 'Not Started';
        }

        if ($progress >= 80) {
            return 'Excellent';
        }
        if ($progress >= 60) {
            return 'Good';
        }
        if ($progress >= 40) {
            return 'Average';
        }

        return 'Needs Help';
    }
}
