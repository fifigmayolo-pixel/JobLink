<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'security_question')) {
                $table->string('security_question')->nullable()->after('activated_at');
            }
            if (! Schema::hasColumn('users', 'security_answer')) {
                $table->string('security_answer')->nullable()->after('security_question');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['security_question', 'security_answer']);
        });
    }
};