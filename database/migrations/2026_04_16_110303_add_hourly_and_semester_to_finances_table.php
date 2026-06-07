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
        Schema::table('finances', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->decimal('hours', 8, 2)->default(0);
            $table->decimal('hourly_rate', 8, 2)->default(0);
            $table->decimal('total_due', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finances', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['semester_id', 'hours', 'hourly_rate', 'total_due']);
        });
    }
};
