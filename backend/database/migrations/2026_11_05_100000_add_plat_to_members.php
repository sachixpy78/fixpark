<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('plat_1', 20)->nullable()->after('telepon');
            $table->string('plat_2', 20)->nullable()->after('plat_1');
            $table->string('plat_3', 20)->nullable()->after('plat_2');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['plat_1', 'plat_2', 'plat_3']);
        });
    }
};

