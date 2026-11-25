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
        Schema::create('accommodation_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_type_id')->constrained()->restrictOnDelete();
            $table->string('name', 100)->unique();
            $table->string('code', 30)->unique();
            $table->unsignedSmallInteger('capacity');
            $table->string('status', 20)->default('available');
            $table->string('location', 150)->nullable();
            $table->json('amenities')->nullable();
            $table->json('media')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_units');
    }
};
