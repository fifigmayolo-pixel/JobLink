<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_change_logs', function (Blueprint $table) {
            $table->text('details')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('field_change_logs', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};