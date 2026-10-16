<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Fail-closed default: anything created without an explicit status
            // (a seeder, a factory, a future code path) waits for review.
            $table->string('approval_status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->index('approval_status');
        });

        // Everything posted before approvals existed was already live — keep it
        // live. Query builder (not Eloquent) so soft-deleted rows are covered too.
        DB::table('job_postings')->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'reviewed_at', 'rejection_reason']);
        });
    }
};