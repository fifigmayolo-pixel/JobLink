<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This replaces Laravel's default create_users_table migration.
     * Keep the same filename so it overwrites the stock file when copied in.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['peso', 'job_coordinator', 'company', 'student', 'alumni']);
            $table->string('student_id_number')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->boolean('must_change_password')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};