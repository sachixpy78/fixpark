<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
 {
    /**
     * Tambahkan kolom no_telp ke tabel data
     */
    public function up(): void
    {
        Schema::table('data', function (Blueprint $table) {
            $table->string('no_telp', 20)->nullable()->after('alamat');
        });
    }

    /**
     * Hapus kolom no_telp jika di-rollback
     */
    public function down(): void
    {
        Schema::table('data', function (Blueprint $table) {
            $table->dropColumn('no_telp');
        });
    }
};