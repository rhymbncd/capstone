<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * student_progress and student_quiz_answers are now written and read
     * exclusively through authenticated Laravel controllers
     * (Student\ProgressController, Student\QuizAnswerController, and the
     * teacher/admin read paths that already existed). The public anon key
     * must not touch them — the client-side `session_id=eq.X` filter was
     * never an access boundary, so anyone holding the key could read every
     * student's scores/answers and overwrite any student's progress.
     *
     * The application's own Eloquent connection authenticates as the
     * privileged SUPABASE_DB_USERNAME, not `anon`/`authenticated`, so these
     * revokes do not affect the new controllers.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('REVOKE ALL ON student_progress FROM anon');
        DB::statement('REVOKE ALL ON student_progress FROM authenticated');
        DB::statement('REVOKE ALL ON student_quiz_answers FROM anon');
        DB::statement('REVOKE ALL ON student_quiz_answers FROM authenticated');
        DB::statement('REVOKE ALL ON SEQUENCE student_quiz_answers_id_seq FROM anon');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — do not restore the leaked default grants.
    }
};
