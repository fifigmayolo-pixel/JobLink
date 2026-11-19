<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('student_alumni_profiles')->cascadeOnDelete();

            // 'nbi' or 'barangay' (see config/clearances.php). One row per profile and type:
            // replacing a file updates the row, so there is never an old file left pointing anywhere.
            $table->string('type', 20);

            // Where the PDF is in the private disk. Never shown to anyone and never used as a link.
            $table->string('file_path');
            $table->unsignedInteger('size_bytes')->default(0);

            // The clearance is valid through this date. The day after, it counts as Expired.
            $table->date('valid_until');

            $table->timestamps();

            $table->unique(['profile_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_clearances');
    }
};
