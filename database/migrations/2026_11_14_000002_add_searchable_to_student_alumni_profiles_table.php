<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_alumni_profiles', function (Blueprint $table) {
            // Opt-in, so it starts OFF for everyone, existing users included.
            $table->boolean('searchable_by_companies')->default(false)->index();
            // When the student turned it on (their consent). Cleared when they turn it off.
            $table->timestamp('searchable_consented_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_alumni_profiles', function (Blueprint $table) {
            $table->dropIndex(['searchable_by_companies']);
            $table->dropColumn(['searchable_by_companies', 'searchable_consented_at']);
        });
    }
};
