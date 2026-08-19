<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * teacher_feedback is only ever read/written through the authenticated
     * Laravel backend (Teacher\FeedbackController / Student\FeedbackController),
     * never via the frontend's Supabase anon key. It was created with
     * Supabase's default privileges, which grant "anon"/"authenticated"
     * full read/write/delete on every new table — silently letting anyone
     * with the public anon key read or tamper with all teacher feedback
     * for every student, bypassing the ownership checks in those
     * controllers entirely. Revoke everything so it's backend-only.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('REVOKE ALL ON teacher_feedback FROM anon');
        DB::statement('REVOKE ALL ON teacher_feedback FROM authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — do not restore the leaked default grants.
    }
};
