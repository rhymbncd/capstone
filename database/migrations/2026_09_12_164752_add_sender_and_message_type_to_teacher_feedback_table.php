<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets a student send a plain message to their teacher through the same
     * table teacher-to-student feedback already uses, instead of a separate
     * conversation table. `sender` distinguishes who wrote each row;
     * `type` gains a 5th 'message' value for student-authored rows, which
     * have no teacher-facing category.
     */
    public function up(): void
    {
        Schema::table('teacher_feedback', function (Blueprint $table) {
            $table->string('sender', 20)->default('teacher')->after('student_id');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            // Laravel's enum() on Postgres is a varchar + CHECK constraint,
            // not a native enum type — named teacher_feedback_type_check by
            // Postgres's own default naming convention (confirmed against
            // the live schema), so drop and re-add it with 'message' allowed.
            DB::statement('ALTER TABLE teacher_feedback DROP CONSTRAINT teacher_feedback_type_check');
            DB::statement("ALTER TABLE teacher_feedback ADD CONSTRAINT teacher_feedback_type_check CHECK (type::text = ANY (ARRAY['encouragement','improvement','praise','reminder','message']::text[]))");

            return;
        }

        // SQLite (used only for the test suite) has no ALTER CONSTRAINT —
        // rebuild the table with the widened CHECK instead of adding
        // doctrine/dbal as a dependency just for this one column's ->change().
        Schema::create('teacher_feedback_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender', 20)->default('teacher');
            $table->enum('type', ['encouragement', 'improvement', 'praise', 'reminder', 'message']);
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'read_at']);
        });

        DB::statement('INSERT INTO teacher_feedback_new (id, teacher_id, student_id, sender, type, message, read_at, created_at, updated_at)
                        SELECT id, teacher_id, student_id, sender, type, message, read_at, created_at, updated_at FROM teacher_feedback');

        Schema::drop('teacher_feedback');
        Schema::rename('teacher_feedback_new', 'teacher_feedback');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('teacher_feedback')->where('type', 'message')->delete();

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE teacher_feedback DROP CONSTRAINT teacher_feedback_type_check');
            DB::statement("ALTER TABLE teacher_feedback ADD CONSTRAINT teacher_feedback_type_check CHECK (type::text = ANY (ARRAY['encouragement','improvement','praise','reminder']::text[]))");

            Schema::table('teacher_feedback', function (Blueprint $table) {
                $table->dropColumn('sender');
            });

            return;
        }

        Schema::create('teacher_feedback_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['encouragement', 'improvement', 'praise', 'reminder']);
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'read_at']);
        });

        DB::statement('INSERT INTO teacher_feedback_new (id, teacher_id, student_id, type, message, read_at, created_at, updated_at)
                        SELECT id, teacher_id, student_id, type, message, read_at, created_at, updated_at FROM teacher_feedback');

        Schema::drop('teacher_feedback');
        Schema::rename('teacher_feedback_new', 'teacher_feedback');
    }
};
