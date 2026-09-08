<?php

namespace App\Observers;

use App\Models\QuizPublished;
use App\Models\StudentProgress;

class QuizPublishedObserver
{
    /**
     * The pre/post attempt phases whose completion depends on the published
     * quiz. Reading progress (phase "reading") tracks the module PDF, not
     * the quiz, so it is deliberately left untouched.
     *
     * @var list<string>
     */
    private const QUIZ_PHASES = ['pre', 'post'];

    /**
     * The question columns whose contents students are actually graded on.
     * A change to either means the answers on file were given against a
     * different set of questions.
     *
     * @var list<string>
     */
    private const QUESTION_COLUMNS = ['pretest', 'posttest'];

    /**
     * Handle the QuizPublished "updated" event.
     *
     * Replacing the questions for an already-published topic (the teacher
     * regenerates or edits the quiz and re-publishes) leaves every student
     * with a recorded score against questions that no longer exist, so the
     * topic would stay "done" and later modules unlocked on a quiz they
     * never actually took. Clearing the rows forces them to retake it.
     * A re-publish that only bumps published_at is ignored.
     */
    public function updated(QuizPublished $quizPublished): void
    {
        if ($quizPublished->wasChanged(self::QUESTION_COLUMNS)) {
            $this->resetStudentProgress($quizPublished->topic_key);
        }
    }

    /**
     * Handle the QuizPublished "deleted" event.
     *
     * Removing the published quiz for a topic pulls the questions every
     * enrolled student answered to complete it, so their recorded
     * pre/post attempts for that topic can no longer be trusted. Clearing
     * those rows drops the topic back to "not done"; the modules page then
     * re-locks every later topic in sequence on the student's next visit,
     * because it only unlocks up to the first topic without a passed
     * post-test.
     */
    public function deleted(QuizPublished $quizPublished): void
    {
        $this->resetStudentProgress($quizPublished->topic_key);
    }

    /**
     * Wipe every student's pre/post attempts for a topic.
     */
    private function resetStudentProgress(string $topicKey): void
    {
        StudentProgress::query()
            ->where('topic_key', $topicKey)
            ->whereIn('phase', self::QUIZ_PHASES)
            ->delete();
    }
}
