<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProgress extends Model
{
    use HasFactory;

    /**
     * Attempt phases whose completion is defined by a teacher-published
     * quiz. Once that quiz is unpublished these rows no longer describe
     * anything a student can currently do, so they are hidden from the
     * dashboard and pruned from the table. Reading progress (phase
     * "reading") tracks the module PDF instead and is left untouched.
     *
     * @var list<string>
     */
    public const QUIZ_PHASES = ['pre', 'post'];

    /**
     * Topic keys for attempts that are not backed by a teacher-published
     * quiz and must survive an unpublish — currently just the dashboard's
     * own summative review test.
     *
     * @var list<string>
     */
    public const SELF_DIRECTED_TOPIC_KEYS = ['summative'];

    protected $table = 'student_progress';

    protected $fillable = [
        'session_id',
        'topic_key',
        'phase',
        'score',
        'total',
        'passed',
        'student_name',
        'module_1_pct',
        'module_2_pct',
        'module_3_pct',
        'overall_pct',
    ];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'score' => 'integer',
            'total' => 'integer',
            'module_1_pct' => 'integer',
            'module_2_pct' => 'integer',
            'module_3_pct' => 'integer',
            'overall_pct' => 'integer',
        ];
    }
}
