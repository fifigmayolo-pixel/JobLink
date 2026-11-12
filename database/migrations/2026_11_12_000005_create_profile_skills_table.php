<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')
                ->constrained('student_alumni_profiles')->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();

            // Also serves as the index on profile_id. Case-insensitive
            // duplicates ("php" vs "PHP") are removed by the Form Request.
            $table->unique(['profile_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_skills');
    }
};