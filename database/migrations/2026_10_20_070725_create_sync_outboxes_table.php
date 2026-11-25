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
        Schema::create('sync_outboxes', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->uuid('installation_uuid')->index();
            $table->string('aggregate_type', 80);
            $table->string('aggregate_id', 80);
            $table->unsignedBigInteger('aggregate_version');
            $table->string('event_type', 120);
            $table->json('payload');
            $table->char('checksum', 64);
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->index();
            $table->timestamp('acked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['aggregate_type', 'aggregate_id', 'aggregate_version'], 'sync_outbox_aggregate_version_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_outboxes');
    }
};
