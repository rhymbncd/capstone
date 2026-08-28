<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * module_status is now read and written only through the authenticated
     * Teacher/Admin/Student ModuleController classes. The anon key could
     * previously approve its own submissions, reject others', edit any
     * module's metadata, or wipe the moderation queue.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('REVOKE ALL ON module_status FROM anon');
        DB::statement('REVOKE ALL ON module_status FROM authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — do not restore the leaked default grants.
    }
};
