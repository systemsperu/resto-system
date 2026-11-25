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
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->uuid('conflict_uuid')->unique();
            $table->uuid('event_uuid')->index();
            $table->string('aggregate_type', 80);
            $table->string('aggregate_id', 80);
            $table->json('local_payload')->nullable();
            $table->json('remote_payload')->nullable();
            $table->string('reason', 255);
            $table->string('status', 20)->default('open')->index();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
    }
};
