<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_alumni_profiles', function (Blueprint $table) {
            // When the owner agreed, on the upload page, that companies they apply to can open
            // their clearances. Empty until the first upload. Clearances can show sensitive
            // personal information, so this is asked separately from the general terms.
            $table->timestamp('clearance_consent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_alumni_profiles', function (Blueprint $table) {
            $table->dropColumn('clearance_consent_at');
        });
    }
};
