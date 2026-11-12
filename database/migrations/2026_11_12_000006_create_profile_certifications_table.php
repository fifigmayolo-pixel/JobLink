<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->index()
                ->constrained('student_alumni_profiles')->cascadeOnDelete();
            $table->string('kind', 20)->default('certification'); // certification, seminar
            $table->string('title', 150);
            $table->string('issuer', 150)->nullable();
            $table->date('date_received')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_certifications');
    }
};