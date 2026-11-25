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
        Schema::create('cloud_backups', function (Blueprint $table) {
            $table->id();
            $table->uuid('backup_uuid')->unique();
            $table->string('disk', 40);
            $table->string('path');
            $table->char('checksum', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->boolean('encrypted')->default(true);
            $table->boolean('verified')->default(false);
            $table->string('status', 20)->default('created')->index();
            $table->timestamp('verified_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloud_backups');
    }
};
