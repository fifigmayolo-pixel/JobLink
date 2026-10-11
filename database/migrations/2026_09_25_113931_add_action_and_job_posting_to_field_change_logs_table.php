<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_change_logs', function (Blueprint $table) {
            $table->string('action')->default('field_correction')->after('id');
            $table->foreignId('job_posting_id')->nullable()->after('changed_by')
                ->constrained('job_postings')->nullOnDelete();
        });

        Schema::table('field_change_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('field_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('field_change_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_posting_id');
            $table->dropColumn('action');
        });

        Schema::table('field_change_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->string('field_name')->nullable(false)->change();
        });
    }
};