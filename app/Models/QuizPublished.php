<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

/**
 * A quiz a teacher has published for students, keyed by topic. The
 * pretest/posttest/activity columns hold JSON strings exactly as the
 * frontend serialises them — kept as strings so the student and teacher
 * parsers (safeParseJSON) work unchanged.
 */
class QuizPublished extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'quiz_published';

    protected $fillable = [
        'topic_key',
        'pretest',
        'posttest',
        'activity',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
