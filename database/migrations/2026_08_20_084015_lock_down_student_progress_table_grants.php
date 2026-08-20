<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * student_progress had no grant-audit migration in the codebase at
     * all. Its live privileges (confirmed via information_schema before
     * writing this) were already SELECT/INSERT/UPDATE only — no DELETE,
     * no TRUNCATE/REFERENCES/TRIGGER — so this documents the existing
     * grant rather than tightening it further.
     *
     * SELECT is intentionally kept, unlike activity_logs (which was fully
     * moved to insert-only). Unlike that table, student_progress reads
     * are load-bearing in three separate places today: the student's own
     * "resume where I left off" / completed-topics check in
     * module.blade.php, the dashboard progress summary in
     * student_dashboard.js, and the admin Analytics tab in
     * admin_dashboard.js. This app has no Supabase Row-Level-Security, so
     * there is no way to grant "a student may read only their own rows"
     * at the Postgres level — the client-side `session_id=eq.X` filters
     * these call sites use are a UX convenience, not a real access
     * boundary, meaning anyone holding the public anon key can already
     * read every student's name/scores by omitting that filter.
     *
     * Revoking SELECT here would break three working features at once.
     * Properly closing this requires moving all three read paths behind
     * authenticated Laravel endpoints first (mirroring how activity_logs
     * was migrated) — deliberately scoped as separate, dedicated follow-up
     * work rather than rushed into this grant-documentation pass, since it
     * touches the core quiz-taking data path.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('REVOKE ALL ON student_progress FROM anon');
        DB::statement('REVOKE ALL ON student_progress FROM authenticated');
        DB::statement('GRANT SELECT, INSERT, UPDATE ON student_progress TO anon');
        DB::statement('GRANT SELECT, INSERT, UPDATE ON student_progress TO authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — do not restore the leaked default grants.
    }
};
