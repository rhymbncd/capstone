<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * module_status had no grant-audit migration in the codebase at all —
     * its live privileges (confirmed via information_schema before writing
     * this) already matched real usage exactly (the admin/teacher module
     * moderation queue does genuine SELECT/INSERT/UPDATE/DELETE via the
     * anon key, with no equivalent Laravel-backend path), so this doesn't
     * change behavior. It makes the grant explicit and documented — same
     * as teacher_feedback/student_quiz_answers/activity_logs — so a future
     * table recreation in Supabase can't silently regress to its default
     * "anon gets everything including TRUNCATE/REFERENCES/TRIGGER" grant.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('REVOKE ALL ON module_status FROM anon');
        DB::statement('REVOKE ALL ON module_status FROM authenticated');
        DB::statement('GRANT SELECT, INSERT, UPDATE, DELETE ON module_status TO anon');
        DB::statement('GRANT SELECT, INSERT, UPDATE, DELETE ON module_status TO authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — do not restore the leaked default grants.
    }
};
