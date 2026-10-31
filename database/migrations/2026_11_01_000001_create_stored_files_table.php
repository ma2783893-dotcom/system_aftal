<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Uploaded files (CVs, photos, voucher attachments) are kept inside MySQL.
 * Railway wipes the app's disk on every deploy, but the database lives on a volume,
 * so files stored here survive deploys and restarts.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('stored_files')) {
            return;
        }

        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 20);              // cv | photo | voucher
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->binary('content');
            $table->timestamps();
            $table->index(['user_id', 'kind']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE stored_files MODIFY content LONGBLOB NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
