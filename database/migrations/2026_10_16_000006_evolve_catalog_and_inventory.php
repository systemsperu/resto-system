<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock', 12, 3)->nullable()->change();
            $table->string('unit', 20)->default('unit')->after('stock');
            $table->boolean('track_stock')->default(true)->after('unit');
            $table->decimal('low_stock_threshold', 12, 3)->nullable()->after('track_stock');
        });
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
            $table->decimal('old_stock', 12, 3)->nullable()->change();
            $table->decimal('new_stock', 12, 3)->nullable()->change();
        });

        Schema::create('modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedTinyInteger('min_selections')->default(0);
            $table->unsignedTinyInteger('max_selections')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('modifier_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('price_delta', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('modifier_group_product', function (Blueprint $table) {
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['modifier_group_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modifier_group_product');
        Schema::dropIfExists('modifier_options');
        Schema::dropIfExists('modifier_groups');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['unit', 'track_stock', 'low_stock_threshold']);
            $table->integer('stock')->nullable()->change();
        });
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->integer('old_stock')->nullable()->change();
            $table->integer('new_stock')->nullable()->change();
        });
    }
};
