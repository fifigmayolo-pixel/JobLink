<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_alumni_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['student', 'alumni']);
            $table->string('profile_picture')->nullable();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('gender')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('nationality')->nullable();
            $table->string('course_program');
            $table->string('year_level')->nullable();
            $table->string('section')->nullable();
            $table->unsignedSmallInteger('year_graduated')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('house_street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('municipality_city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_alumni_profiles');
    }
};