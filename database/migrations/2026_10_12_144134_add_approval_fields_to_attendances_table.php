<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status')->default('normal')->after('is_absent');
            $table->time('proposed_clock_out')->nullable()->after('status');
            $table->string('appeal_reason')->nullable()->after('proposed_clock_out');
            $table->foreignId('approved_by')->nullable()->after('appeal_reason')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'proposed_clock_out', 'appeal_reason', 'approved_by']);
        });
    }
};
