<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'note')) {
                $table->string('note', 500)->nullable();
            }
            if (!Schema::hasColumn('attendances', 'manual')) {
                $table->boolean('manual')->default(false);   // entered or changed by the administration
            }
            if (!Schema::hasColumn('attendances', 'edited_by')) {
                $table->unsignedBigInteger('edited_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            foreach (['note', 'manual', 'edited_by'] as $c) {
                if (Schema::hasColumn('attendances', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
