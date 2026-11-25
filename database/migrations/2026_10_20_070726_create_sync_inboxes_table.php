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
        Schema::create('sync_inboxes', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->uuid('source_installation_uuid')->index();
            $table->string('event_type', 120);
            $table->json('payload');
            $table->char('checksum', 64);
            $table->string('status', 20)->default('received')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_inboxes');
    }
};
