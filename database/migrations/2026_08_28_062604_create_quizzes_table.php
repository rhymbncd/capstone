<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarded with hasTable() — the table already exists on production
     * Supabase; this creates it only for fresh/test environments.
     */
    public function up(): void
    {
        if (Schema::hasTable('quizzes')) {
            return;
        }

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('topic')->nullable();
            $table->string('activity_label')->nullable();
            $table->string('grade')->nullable();
            $table->string('difficulty')->nullable();
            $table->text('pretest')->nullable();
            $table->text('posttest')->nullable();
            $table->text('activity')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
