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
        if (Schema::hasTable('quiz_custom_topics')) {
            return;
        }

        Schema::create('quiz_custom_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('module_key')->nullable();
            $table->string('topic_key');
            $table->string('topic_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_custom_topics');
    }
};
