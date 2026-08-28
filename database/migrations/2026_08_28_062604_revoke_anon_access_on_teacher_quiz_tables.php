<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * quiz_published, quiz_custom_topics and quizzes are now read and
     * written exclusively through authenticated Laravel controllers
     * (Teacher\PublishedQuizController / CustomTopicController /
     * QuizDraftController and Student\PublishedQuizController). The public
     * anon key must not touch them — with the default Supabase grant,
     * anyone holding the key could rewrite or delete every teacher's quiz
     * content, and inject markup that renders in students' browsers.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['quiz_published', 'quiz_custom_topics', 'quizzes'] as $table) {
            DB::statement("REVOKE ALL ON {$table} FROM anon");
            DB::statement("REVOKE ALL ON {$table} FROM authenticated");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — do not restore the leaked default grants.
    }
};
