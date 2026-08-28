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
        if (Schema::hasTable('module_status')) {
            return;
        }

        Schema::create('module_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file_name')->unique();
            $table->text('file_url')->nullable();
            $table->string('status')->default('pending');
            $table->string('module_title')->nullable();
            $table->string('module_topic')->nullable();
            $table->text('module_desc')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_status');
    }
};
