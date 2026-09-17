<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\StudentProgress;
use App\Models\StudentQuizAnswer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ClassRecordController extends Controller
{
    /**
     * The real curriculum topics ("lessons"), matching CURRICULUM_TOPICS in
     * StudentController and TOPIC_ORDER in resources/views/dashboard/module.blade.php.
     *
     * @var array<string, string>
     */
    private const TOPICS = [
        'ari' => 'Arithmetic Sequence',
        'geo' => 'Geometric Sequence',
        'har' => 'Harmonic Sequence',
        'fib' => 'Fibonacci Sequence',
        'fin' => 'Finite and Infinite Sequence',
        'div' => 'Division of Polynomials',
        'rem' => 'Remainder & Factor Theorem',
        'poly' => 'Polynomial Equations',
        'rat' => 'Rational Functions',
        'rad' => 'Radical Equations',
        'exp' => 'Exponential Functions',
        'log' => 'Logarithmic Functions',
    ];

    /**
     * Raw pretest/posttest/activity/summative scores — score/total per
     * lesson, not a percentage — for every student in the authenticated
     * teacher's sections. This is what a teacher's physical class record
     * actually lists, one column per lesson.
     */
    public function index(): JsonResponse
    {
        $teacher = Auth::user();

        if (! $teacher || $teacher->role !== 'teacher') {
            return response()->json(['topics' => [], 'students' => []]);
        }

        $topicKeys = array_keys(self::TOPICS);

        $students = User::where('role', 'student')
            ->whereHas('section', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->with('section:id,name')
            ->get(['id', 'name', 'student_id', 'section_id']);

        $studentIds = $students->pluck('id')->map(fn ($id) => (string) $id)->all();

        $progressRows = StudentProgress::query()
            ->whereIn('session_id', $studentIds)
            ->where(function ($query) use ($topicKeys) {
                $query->where(function ($q) use ($topicKeys) {
                    $q->whereIn('topic_key', $topicKeys)->whereIn('phase', ['pre', 'post']);
                })->orWhere('topic_key', 'summative');
            })
            ->get(['session_id', 'topic_key', 'phase', 'score', 'total'])
            ->groupBy('session_id');

        $activityRows = StudentQuizAnswer::query()
            ->whereIn('session_id', $studentIds)
            ->where('phase', 'activity')
            ->whereIn('topic_key', $topicKeys)
            ->get(['session_id', 'topic_key', 'score', 'total'])
            ->groupBy('session_id');

        $studentData = $students->map(function (User $student) use ($progressRows, $activityRows, $topicKeys) {
            $rows = $progressRows->get((string) $student->id, collect());
            $activity = $activityRows->get((string) $student->id, collect())->keyBy('topic_key');

            $summativeRow = $rows->first(fn ($row) => $row->topic_key === 'summative');

            return [
                'id' => $student->id,
                'name' => $student->name,
                'studentId' => $student->student_id,
                'section_id' => $student->section_id,
                'section' => $student->section?->name,
                'pretest' => $this->scoresByTopic($rows->where('phase', 'pre'), $topicKeys),
                'posttest' => $this->scoresByTopic($rows->where('phase', 'post'), $topicKeys),
                'activity' => $this->scoresByTopic($activity, $topicKeys),
                'summative' => $summativeRow ? ['score' => $summativeRow->score, 'total' => $summativeRow->total] : null,
            ];
        });

        return response()->json([
            'topics' => collect(self::TOPICS)->map(fn ($name, $key) => ['key' => $key, 'name' => $name])->values(),
            'students' => $studentData->values(),
        ]);
    }

    /**
     * Maps a set of rows (keyed or plain) to one raw score/total per topic,
     * in curriculum order, with a null for any lesson not yet attempted.
     *
     * @param  Collection<int|string, StudentProgress|StudentQuizAnswer>  $rows
     * @param  list<string>  $topicKeys
     * @return array<string, array{score: int, total: int}|null>
     */
    private function scoresByTopic(Collection $rows, array $topicKeys): array
    {
        $byTopic = $rows->keyBy('topic_key');

        return collect($topicKeys)->mapWithKeys(function (string $key) use ($byTopic) {
            $row = $byTopic->get($key);

            return [$key => $row ? ['score' => $row->score, 'total' => $row->total] : null];
        })->all();
    }
}
