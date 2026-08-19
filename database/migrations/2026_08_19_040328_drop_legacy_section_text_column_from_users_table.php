<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `users` had a leftover `section` text column (distinct from the real
     * `section_id` foreign key), confirmed empty on every row and never
     * intentionally read or written anywhere in the app. Because Eloquent
     * checks raw attributes before relationship methods, this silently
     * shadowed the `section()` BelongsTo relationship on every User
     * instance: `$user->section` always returned null instead of the
     * related Section model. That broke the teacher/student ownership
     * check in Teacher\StudentApprovalController, so a teacher could never
     * successfully approve/reject/reset any student — the action always
     * silently failed with "Invalid user role."
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'section')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('section');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'section')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('section')->nullable();
            });
        }
    }
};
