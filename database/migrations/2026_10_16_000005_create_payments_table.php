<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('cash_register_id')->nullable()->constrained('cash_registers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('method', 20);
            $table->decimal('amount', 10, 2);
            $table->decimal('received_amount', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('confirmed');
            $table->timestamp('paid_at');
            $table->timestamps();
            $table->index(['cash_register_id', 'method', 'status']);
        });

        DB::table('orders')->where('status', 'completed')->orderBy('id')->chunkById(200, function ($orders): void {
            foreach ($orders as $order) {
                DB::table('payments')->insert([
                    'order_id' => $order->id,
                    'cash_register_id' => $order->cash_register_id,
                    'user_id' => $order->user_id,
                    'method' => in_array($order->payment_method, ['cash', 'card', 'transfer', 'yape', 'plin'], true) ? $order->payment_method : 'cash',
                    'amount' => $order->total,
                    'received_amount' => $order->received_amount,
                    'change_amount' => $order->change_amount ?? 0,
                    'status' => 'confirmed',
                    'paid_at' => $order->updated_at ?? now(),
                    'created_at' => $order->updated_at ?? now(),
                    'updated_at' => $order->updated_at ?? now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
