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
     * Backs the one-attempt-only rule (pre-test/post-test/summative) with a
     * real database constraint, not just an application-level check —
     * matches the unique key student_quiz_answers already has. Guarded with
     * getIndexes() because production's student_progress table already has
     * this exact constraint, added outside Laravel's migration history the
     * same way the table itself was (see create_student_progress_table).
     *
     * Any pre-existing duplicate (session_id, topic_key, phase) rows —
     * which should never happen since every write so far has gone through
     * updateOrCreate, but could exist from a race — are collapsed to their
     * most recent row first so the unique index can be added on a fresh
     * environment that doesn't already have it.
     */
    public function up(): void
    {
        $alreadyExists = collect(Schema::getIndexes('student_progress'))
            ->contains('name', 'student_progress_session_id_topic_key_phase_unique');

        if ($alreadyExists) {
            return;
        }

        $duplicateGroups = DB::table('student_progress')
            ->select('session_id', 'topic_key', 'phase')
            ->whereNotNull('session_id')
            ->whereNotNull('topic_key')
            ->whereNotNull('phase')
            ->groupBy('session_id', 'topic_key', 'phase')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $idsToDelete = DB::table('student_progress')
                ->where('session_id', $group->session_id)
                ->where('topic_key', $group->topic_key)
                ->where('phase', $group->phase)
                ->orderByDesc('id')
                ->pluck('id')
                ->skip(1);

            DB::table('student_progress')->whereIn('id', $idsToDelete)->delete();
        }

        Schema::table('student_progress', function (Blueprint $table) {
            $table->unique(['session_id', 'topic_key', 'phase']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropUnique(['session_id', 'topic_key', 'phase']);
        });
    }
};
