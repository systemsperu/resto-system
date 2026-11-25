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
        Schema::create('accommodation_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason', 30);
            $table->text('notes')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['accommodation_unit_id', 'starts_on', 'ends_on'], 'accommodation_blocks_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_blocks');
    }
};
