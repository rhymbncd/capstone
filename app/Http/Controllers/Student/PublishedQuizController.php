<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizCustomTopic;
use App\Models\QuizPublished;
use Illuminate\Http\JsonResponse;

/**
 * Read-only feed the student modules page needs: the teacher-published
 * quizzes (which replace the built-in questions) plus custom topic names.
 */
class PublishedQuizController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'published' => QuizPublished::get(['topic_key', 'pretest', 'posttest', 'activity']),
            'customTopics' => QuizCustomTopic::get(['topic_key', 'topic_name', 'module_key']),
        ]);
    }
}
