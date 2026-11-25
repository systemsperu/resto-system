<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SECRET_KEYS = [
        'sunat_sol_user',
        'sunat_sol_pass',
        'sunat_cert_password',
    ];

    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('is_secret')->default(false)->after('value');
        });

        DB::table('settings')
            ->whereIn('key', self::SECRET_KEYS)
            ->orderBy('id')
            ->each(function (object $setting): void {
                DB::table('settings')->where('id', $setting->id)->update([
                    'value' => filled($setting->value) ? Crypt::encryptString($setting->value) : null,
                    'is_secret' => true,
                ]);
            });
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', self::SECRET_KEYS)
            ->where('is_secret', true)
            ->orderBy('id')
            ->each(function (object $setting): void {
                DB::table('settings')->where('id', $setting->id)->update([
                    'value' => filled($setting->value) ? Crypt::decryptString($setting->value) : null,
                ]);
            });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('is_secret');
        });
    }
};
