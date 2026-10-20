<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'check_out_time')) {
                $table->time('check_out_time')->nullable()->after('check_in_time');
            }
            if (!Schema::hasColumn('attendances', 'check_out_latitude')) {
                $table->decimal('check_out_latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('attendances', 'check_out_longitude')) {
                $table->decimal('check_out_longitude', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('attendances', 'check_out_distance_meters')) {
                $table->decimal('check_out_distance_meters', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('attendances', 'check_out_verified')) {
                $table->boolean('check_out_verified')->default(false);
            }
            if (!Schema::hasColumn('attendances', 'device_id')) {
                $table->string('device_id', 64)->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'device_id')) {
                $table->string('device_id', 64)->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'device_bound_at')) {
                $table->timestamp('device_bound_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_out_time', 'check_out_latitude', 'check_out_longitude',
                                'check_out_distance_meters', 'check_out_verified', 'device_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['device_id']);
            $table->dropColumn(['device_id', 'device_bound_at', 'must_change_password']);
        });
    }
};
