<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('attendances', 'auto_checkout')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->boolean('auto_checkout')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'auto_checkout')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('auto_checkout');
            });
        }
    }
};
