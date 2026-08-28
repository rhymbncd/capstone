<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A teacher-defined extra topic that appears in the quiz generator and,
 * once named, renames the matching topic on the student modules page.
 */
class QuizCustomTopic extends Model
{
    protected $table = 'quiz_custom_topics';

    protected $fillable = [
        'module_key',
        'topic_key',
        'topic_name',
    ];
}
