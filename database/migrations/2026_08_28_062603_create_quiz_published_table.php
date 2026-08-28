<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarded with hasTable() because this table already exists on the
     * production Supabase database (created outside Laravel's migration
     * history); this only creates it for a fresh environment, including the
     * sqlite database the tests run against.
     */
    public function up(): void
    {
        if (Schema::hasTable('quiz_published')) {
            return;
        }

        Schema::create('quiz_published', function (Blueprint $table) {
            $table->id();
            $table->string('topic_key')->unique();
            $table->text('pretest')->nullable();
            $table->text('posttest')->nullable();
            $table->text('activity')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_published');
    }
};
