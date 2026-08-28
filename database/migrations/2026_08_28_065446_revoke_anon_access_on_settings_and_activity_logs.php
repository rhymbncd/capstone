<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * platform_settings is now read/written only through
     * Admin\PlatformSettingController, and activity_logs is written only
     * through ActivityController::store (plus the server-side
     * ActivityLog::record calls that always existed). The anon key keeps
     * nothing: it could previously seed arbitrary settings and forge audit
     * log entries under any name/role.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['platform_settings', 'activity_logs'] as $table) {
            DB::statement("REVOKE ALL ON {$table} FROM anon");
            DB::statement("REVOKE ALL ON {$table} FROM authenticated");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — do not restore the leaked default grants.
    }
};
