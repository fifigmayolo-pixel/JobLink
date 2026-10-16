<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('student_id_number', 50);
            $table->string('full_name');
            $table->string('email');
            $table->string('applicant_type', 20);          // student | alumni
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // account created on approval
            $table->timestamps();

            $table->index(['student_id_number', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_requests');
    }
};