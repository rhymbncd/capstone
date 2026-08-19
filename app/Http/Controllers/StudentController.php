<?php

namespace App\Http\Controllers;

use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
    private const CURRICULUM_TOPICS = [
        'ari', 'geo', 'har', 'fib', 'fin',
        'div', 'rem', 'poly',
        'rat', 'rad', 'exp', 'log',
    ];

    /**
     * Get all students enrolled in the authenticated teacher's sections,
     * with real completion progress computed from student_progress.
     */
    public function getTeacherStudents(): JsonResponse
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

        $progressRows = StudentProgress::query()
            ->whereIn('session_id', $studentIds)
            ->whereIn('phase', ['pre', 'post'])
            ->whereIn('topic_key', self::CURRICULUM_TOPICS)
            ->get(['session_id', 'topic_key', 'phase', 'score', 'total'])
            ->groupBy('session_id');

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
                'status' => $this->getStudentStatus($student, $progress),
                'progress' => $progress,
                'avgPre' => $this->averageScorePercent($rows->where('phase', 'pre')),
                'avgPost' => $this->averageScorePercent($postRows),
                'lastActive' => $student->updated_at->diffForHumans(),
            ];
        });

        return response()->json(['students' => $studentData]);
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
     */
    private function getStudentStatus(User $student, int $progress): string
    {
        if ($student->approval_status !== 'approved') {
            return 'Pending';
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
