<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracer_study_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tracer_study_link_id')->constrained('tracer_study_links')->cascadeOnDelete();
            $table->timestamp('accessed_at');
            $table->timestamps();

            // One record per alumnus per study, enforced by the database.
            $table->unique(['user_id', 'tracer_study_link_id']);
            $table->index('tracer_study_link_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracer_study_accesses');
    }
};