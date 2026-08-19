<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores each student's actual answers for a pre-test/post-test/activity
     * attempt so a teacher can review what a student answered, not just the
     * score. The anon key needs INSERT, UPDATE, and SELECT — Postgres
     * requires SELECT for an `INSERT ... ON CONFLICT DO UPDATE` upsert to
     * evaluate the conflict target, and the student-side JS upserts on
     * (session_id, topic_key, phase) the same way student_progress does.
     * Teacher reads still go through the authenticated Laravel backend
     * (Teacher\StudentAnswersController), which scopes results to the
     * requesting teacher's own section regardless of this grant.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_quiz_answers')) {
            return;
        }

        Schema::create('student_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->text('student_name')->nullable();
            $table->string('topic_key')->nullable();
            $table->string('phase')->nullable();
            $table->json('answers')->nullable();
            $table->integer('score')->nullable();
            $table->integer('total')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'topic_key', 'phase']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            // Supabase's default privileges grant "anon"/"authenticated" ALL
            // on newly created tables, so start from nothing and re-grant
            // only what the student-facing writer actually needs.
            DB::statement('REVOKE ALL ON student_quiz_answers FROM anon');
            DB::statement('REVOKE ALL ON student_quiz_answers FROM authenticated');
            DB::statement('GRANT INSERT, UPDATE, SELECT ON student_quiz_answers TO anon');
            DB::statement('GRANT USAGE, SELECT ON SEQUENCE student_quiz_answers_id_seq TO anon');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_quiz_answers');
    }
};
