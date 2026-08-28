<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * module_status.file_url held the public Storage URL under the old
     * anon-key setup. The buckets are private now and every read goes
     * through a signed URL built from file_name, so the controller no
     * longer writes file_url — but the production column is still NOT NULL.
     * Drop the constraint.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE module_status ALTER COLUMN file_url DROP NOT NULL');
    }

    public function down(): void
    {
        // Intentionally left blank.
    }
};
