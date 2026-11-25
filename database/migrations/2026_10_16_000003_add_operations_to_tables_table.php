<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->foreignId('assigned_employee_id')->nullable()->after('area_id')
                ->constrained('employees')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_employee_id');
            $table->dropColumn('is_active');
        });
    }
};
