<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('lodging_reservation_id')->nullable()->after('order_id')->constrained()->restrictOnDelete();
            $table->string('source', 20)->default('restaurant')->after('lodging_reservation_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->index(['lodging_reservation_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payments')->where('source', 'lodging')->delete();
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['lodging_reservation_id', 'status']);
            $table->dropConstrainedForeignId('lodging_reservation_id');
            $table->dropColumn('source');
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
        });
    }
};
