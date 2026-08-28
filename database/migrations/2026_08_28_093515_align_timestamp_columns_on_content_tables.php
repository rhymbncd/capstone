<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The quiz / module tables were created directly in Supabase (outside
     * Laravel's migration history) without Eloquent's created_at/updated_at
     * columns. The models that now own these tables use timestamps, so an
     * insert fails with "column updated_at does not exist" on the existing
     * production database. Add the columns where they're missing.
     *
     * Postgres-only + IF NOT EXISTS, so it is a safe no-op on a fresh
     * database (the create_* migrations already add the columns) and on
     * the sqlite test database.
     *
     * @var list<string>
     */
    private const TABLES = [
        'module_status',
        'quiz_published',
        'quizzes',
        'quiz_custom_topics',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS created_at timestamp without time zone NULL");
            DB::statement("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS updated_at timestamp without time zone NULL");
        }
    }

    public function down(): void
    {
        // Intentionally left blank — dropping the columns would break the models.
    }
};
