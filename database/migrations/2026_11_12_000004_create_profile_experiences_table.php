<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->index()
                ->constrained('student_alumni_profiles')->cascadeOnDelete();
            $table->string('kind', 20)->default('work'); // work, ojt, volunteer
            $table->string('position', 120);
            $table->string('organization', 150);
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = still there
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_experiences');
    }
};