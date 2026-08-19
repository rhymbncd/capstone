<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * quiz_published.topic_key had no unique constraint, so the teacher
     * dashboard's "replace published quiz" upsert (Prefer: resolution=
     * merge-duplicates) had nothing to conflict against and silently
     * inserted a duplicate row per topic instead of replacing it, leaving
     * students reading whichever duplicate PostgREST happened to return
     * first. This makes topic_key unique so the upsert actually upserts.
     */
    public function up(): void
    {
        // Postgres-only repair migration (raw pg_constraint SQL) — a no-op
        // on any other driver, including the sqlite connection used by tests.
        if (DB::connection()->getDriverName() !== 'pgsql' || ! Schema::hasTable('quiz_published')) {
            return;
        }

        $hasConstraint = DB::selectOne(
            "select 1 from pg_constraint where conname = 'quiz_published_topic_key_unique'"
        );

        if (! $hasConstraint) {
            DB::statement('alter table quiz_published add constraint quiz_published_topic_key_unique unique (topic_key)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql' || ! Schema::hasTable('quiz_published')) {
            return;
        }

        DB::statement('alter table quiz_published drop constraint if exists quiz_published_topic_key_unique');
    }
};
