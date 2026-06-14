<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'status')) {
                $table->enum('status', ['present', 'absent', 'late'])->default('present')->after('date');
            }
            if (!Schema::hasColumn('attendances', 'check_in_time')) {
                $table->time('check_in_time')->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendances', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('check_in_time');
            }
            if (!Schema::hasColumn('attendances', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('attendances', 'distance_meters')) {
                $table->decimal('distance_meters', 8, 2)->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('attendances', 'location_verified')) {
                $table->boolean('location_verified')->default(false)->after('distance_meters');
            }
            if (!Schema::hasColumn('attendances', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('location_verified');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['status', 'check_in_time', 'latitude', 'longitude',
                                'distance_meters', 'location_verified', 'ip_address']);
        });
    }
};
