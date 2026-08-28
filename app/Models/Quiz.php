<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

/**
 * A saved quiz draft from the teacher quiz generator. Shared pool — every
 * approved teacher sees the same list (no per-teacher ownership column
 * exists today). pretest/posttest/activity are JSON strings as the
 * frontend serialises them.
 */
class Quiz extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'quizzes';

    protected $fillable = [
        'topic',
        'activity_label',
        'grade',
        'difficulty',
        'pretest',
        'posttest',
        'activity',
    ];
}
