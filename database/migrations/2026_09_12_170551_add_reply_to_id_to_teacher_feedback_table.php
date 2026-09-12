<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Threads a student's reply to the specific teacher message it answers,
     * instead of the two just sharing one flat chronological feed. Nullable
     * self-reference; nullOnDelete so deleting the parent message doesn't
     * also destroy the student's reply content, just detaches it.
     */
    public function up(): void
    {
        Schema::table('teacher_feedback', function (Blueprint $table) {
            $table->foreignId('reply_to_id')->nullable()->after('sender')
                ->constrained('teacher_feedback')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_feedback', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reply_to_id');
        });
    }
};
