<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Defence-in-depth. The `anon` / `authenticated` grants were already
     * revoked table-by-table in the preceding migrations, so those roles
     * cannot reach these tables regardless. Turning RLS on as well makes
     * that explicit to Supabase's own linter and any future policy work,
     * without changing behaviour: each table gets one permissive policy so
     * a role that still *has* table grants (the application's own Postgres
     * connection) keeps full access, while a role with no grant stays
     * blocked at the grant check that runs first.
     *
     * @var list<string>
     */
    private const TABLES = [
        'users',
        'sections',
        'student_progress',
        'student_quiz_answers',
        'quiz_published',
        'quiz_custom_topics',
        'quizzes',
        'module_status',
        'platform_settings',
        'activity_logs',
        'teacher_feedback',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TABLES as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS {$table}_app_access ON {$table}");
            DB::statement("CREATE POLICY {$table}_app_access ON {$table} USING (true) WITH CHECK (true)");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TABLES as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            DB::statement("DROP POLICY IF EXISTS {$table}_app_access ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::selectOne(
            'select 1 from information_schema.tables where table_schema = ? and table_name = ?',
            ['public', $table],
        );
    }
};
