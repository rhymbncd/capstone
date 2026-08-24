<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PostgreSQL does not auto-index the referencing column of a foreign
     * key constraint (unlike MySQL) — ->constrained() gives you the
     * constraint, not an index. These columns are all real FKs, or plain
     * columns filtered on constantly, with no index today.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->index('teacher_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('section_id');
            // role + approval_status are always filtered together on the
            // approval-queue pages (pending/approved/rejected per role) —
            // a composite index serves that, and role-alone lookups too
            // since it's the leading column.
            $table->index(['role', 'approval_status']);
        });

        Schema::table('teacher_feedback', function (Blueprint $table) {
            $table->index('teacher_id');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('student_progress', function (Blueprint $table) {
            // session_id/phase/topic_key are always filtered together when
            // building a teacher's roster progress view, and session_id
            // alone is what User deletion filters on to clean these up.
            $table->index(['session_id', 'phase', 'topic_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropIndex(['teacher_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['section_id']);
            $table->dropIndex(['role', 'approval_status']);
        });

        Schema::table('teacher_feedback', function (Blueprint $table) {
            $table->dropIndex(['teacher_id']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropIndex(['session_id', 'phase', 'topic_key']);
        });
    }
};
