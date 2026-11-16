<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('staff_requests')) {
            Schema::create('staff_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('type', 20);                 // leave | late | early | password
                $table->date('date_from')->nullable();
                $table->date('date_to')->nullable();
                $table->string('time', 5)->nullable();      // HH:MM for late / early
                $table->string('reason', 500)->nullable();
                $table->string('email')->nullable();        // what was typed on the forgot-password form
                $table->string('status', 20)->default('pending')->index();   // pending | approved | rejected
                $table->string('admin_note', 500)->nullable();
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'accuracy')) {
                $table->decimal('accuracy', 10, 2)->nullable();   // GPS accuracy reported by the phone (metres)
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_requests');
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'accuracy')) {
                $table->dropColumn('accuracy');
            }
        });
    }
};
