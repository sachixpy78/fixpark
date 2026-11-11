<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Motor plat slots (maks 3)
            $table->string('motor_1', 20)->nullable()->after('telepon');
            $table->string('motor_2', 20)->nullable()->after('motor_1');
            $table->string('motor_3', 20)->nullable()->after('motor_2');
            // Mobil plat slots (maks 3)
            $table->string('mobil_1', 20)->nullable()->after('motor_3');
            $table->string('mobil_2', 20)->nullable()->after('mobil_1');
            $table->string('mobil_3', 20)->nullable()->after('mobil_2');
        });

        // Migrasi data lama: plat_1/2/3 ke motor_1/2/3
        DB::statement('UPDATE members SET motor_1 = plat_1, motor_2 = plat_2, motor_3 = plat_3 WHERE plat_1 IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['motor_1', 'motor_2', 'motor_3', 'mobil_1', 'mobil_2', 'mobil_3']);
        });
    }
};
