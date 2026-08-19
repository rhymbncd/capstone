<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentQuizAnswer extends Model
{
    protected $table = 'student_quiz_answers';

    protected $fillable = [
        'session_id',
        'student_name',
        'topic_key',
        'phase',
        'answers',
        'score',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'score' => 'integer',
            'total' => 'integer',
        ];
    }
}
