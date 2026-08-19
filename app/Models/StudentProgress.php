<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProgress extends Model
{
    use HasFactory;

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
