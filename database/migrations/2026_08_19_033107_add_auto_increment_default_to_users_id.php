<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * users.id was created as a plain `bigint not null` with no sequence
     * or default attached (unlike every other id column, e.g. sections.id
     * correctly defaults to nextval('sections_id_seq')). Existing rows
     * only exist because their ids were inserted explicitly at some point
     * outside Laravel — any normal Eloquent insert (registration, Google
     * sign-up, admin user creation) fails with a not-null violation.
     */
    public function up(): void
    {
        // Postgres-only repair migration (raw pg_class/nextval SQL) — a no-op
        // on any other driver, including the sqlite connection used by tests.
        if (DB::connection()->getDriverName() !== 'pgsql' || ! Schema::hasTable('users')) {
            return;
        }

        $hasSequence = DB::selectOne("select 1 from pg_class where relkind = 'S' and relname = 'users_id_seq'");

        if (! $hasSequence) {
            DB::statement('create sequence users_id_seq owned by users.id');
        }

        DB::statement("alter table users alter column id set default nextval('users_id_seq')");

        // Keep the sequence ahead of the highest existing id so new inserts
        // never collide with pre-existing rows.
        DB::statement("select setval('users_id_seq', coalesce((select max(id) from users), 1))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql' || ! Schema::hasTable('users')) {
            return;
        }

        DB::statement('alter table users alter column id drop default');
        DB::statement('drop sequence if exists users_id_seq');
    }
};
