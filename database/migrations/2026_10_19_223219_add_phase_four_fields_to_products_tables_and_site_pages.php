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
        Schema::table('tables', function (Blueprint $table) {
            $table->uuid('qr_token')->nullable()->unique()->after('assigned_employee_id');
            $table->timestamp('qr_revoked_at')->nullable()->after('qr_token');
        });
        Schema::table('site_pages', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        Schema::create('location_product', function (Blueprint $table) {
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('promotional_price', 10, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->primary(['location_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_product');
        Schema::table('site_pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
        Schema::table('tables', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn(['qr_token', 'qr_revoked_at']);
        });
    }
};
