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
            // How many people the company wants to hire. Every existing posting gets 1.
            $table->unsignedInteger('slots')->default(1);

            // True only when JobLink itself closed the posting because every slot was taken.
            // A posting a company closed by hand stays false and is never reopened automatically.
            $table->boolean('closed_by_slots')->default(false);
        });

        // A posting that already has people invited to interview would otherwise show
        // "3 of 1 slots filled". Give it as many slots as it has invitations, and close it
        // the same way the app does when the last slot is taken. The company raises the
        // number on the edit page to reopen it.
        $invited = DB::table('job_applications')
            ->where('status', 'interview')
            ->select('job_posting_id', DB::raw('count(*) as total'))
            ->groupBy('job_posting_id')
            ->get();

        foreach ($invited as $row) {
            $postingId = $row->job_posting_id;
            $total = max(1, (int) $row->total);

            DB::table('job_postings')
                ->where('id', $postingId)
                ->update(['slots' => $total]);

            DB::table('job_postings')
                ->where('id', $postingId)
                ->where('status', 'open')
                ->whereNull('deleted_at')
                ->update(['status' => 'closed', 'closed_by_slots' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['slots', 'closed_by_slots']);
        });
    }
};
