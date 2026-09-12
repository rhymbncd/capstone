<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets a student remove a feedback entry from their own inbox without
     * touching the teacher's copy — a hide flag, not a real delete, so the
     * teacher's Feedback History for that student is unaffected (mirrors
     * how deleting a message on one side of a chat doesn't erase it for
     * the other side).
     */
    public function up(): void
    {
        Schema::table('teacher_feedback', function (Blueprint $table) {
            $table->timestamp('student_deleted_at')->nullable()->after('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_feedback', function (Blueprint $table) {
            $table->dropColumn('student_deleted_at');
        });
    }
};
