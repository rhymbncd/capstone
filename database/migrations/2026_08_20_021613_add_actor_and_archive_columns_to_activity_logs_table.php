<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable()->after('user_id');
            $table->string('user_role', 20)->nullable()->after('user_name');
            $table->timestamp('archived_at')->nullable()->after('created_at');

            $table->index('type');
            $table->index('created_at');
            $table->index('archived_at');
            $table->index('user_role');
        });

        // activity_logs was created directly on the production Supabase
        // database (outside Laravel's migration history) with Supabase's
        // default privileges, which grant "anon"/"authenticated" full
        // read/write/delete on every new table. That means anyone holding
        // the public anon key — not just logged-in admins — could browse
        // or wipe every activity record via the Supabase REST API,
        // regardless of the Laravel-side role:admin middleware. The admin
        // dashboard now reads, deletes, and archives logs exclusively
        // through the authenticated Laravel backend, so the only access
        // the frontend anon key still needs is inserting new events.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('REVOKE ALL ON activity_logs FROM anon');
            DB::statement('REVOKE ALL ON activity_logs FROM authenticated');
            DB::statement('GRANT INSERT ON activity_logs TO anon');
            DB::statement('GRANT INSERT ON activity_logs TO authenticated');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['archived_at']);
            $table->dropIndex(['user_role']);
            $table->dropColumn(['user_id', 'user_name', 'user_role', 'archived_at']);
        });

        // Intentionally does not restore the leaked default grants.
    }
};
