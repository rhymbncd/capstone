<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * platform_settings had no grant-audit migration in the codebase at
     * all — an earlier migration's docblock claimed its grants were
     * "already scoped" but no actual GRANT/REVOKE statement backed that
     * claim. Its live privileges (confirmed via information_schema before
     * writing this) already matched real usage exactly (the admin
     * Settings tab does SELECT + upsert via the anon key, no DELETE is
     * ever used), so this doesn't change behavior — it makes the grant
     * explicit and documented so a future table recreation in Supabase
     * can't silently regress to its default over-permissive grant.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('REVOKE ALL ON platform_settings FROM anon');
        DB::statement('REVOKE ALL ON platform_settings FROM authenticated');
        DB::statement('GRANT SELECT, INSERT, UPDATE ON platform_settings TO anon');
        DB::statement('GRANT SELECT, INSERT, UPDATE ON platform_settings TO authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — do not restore the leaked default grants.
    }
};
