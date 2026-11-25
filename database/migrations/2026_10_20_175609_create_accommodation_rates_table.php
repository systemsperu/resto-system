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
        Schema::create('accommodation_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_type_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('nightly_rate', 10, 2);
            $table->unsignedSmallInteger('minimum_nights')->default(1);
            $table->json('weekdays')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['accommodation_type_id', 'starts_on', 'ends_on'], 'accommodation_rates_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_rates');
    }
};
