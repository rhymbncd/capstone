<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Guarded with hasTable() because this table already exists on the
     * production Supabase database (created outside Laravel's migration
     * history); this only creates it for a fresh environment.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_progress')) {
            return;
        }

        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->string('topic_key')->nullable();
            $table->string('phase')->nullable();
            $table->integer('score')->nullable();
            $table->integer('total')->nullable();
            $table->boolean('passed')->nullable();
            $table->text('student_name')->nullable();
            $table->integer('module_1_pct')->nullable();
            $table->integer('module_2_pct')->nullable();
            $table->integer('module_3_pct')->nullable();
            $table->integer('overall_pct')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
