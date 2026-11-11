<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiket_parkir', function (Blueprint $table) {
            $table->id();
            $table->string('no_invoice')->unique();         // INV-20261105-0001
            $table->enum('jenis', ['non_member', 'member']);
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->enum('jenis_kendaraan', ['motor', 'mobil']);
            $table->string('plat_nomor')->nullable();
            $table->string('foto_plat')->nullable();        // path file upload
            $table->string('barcode')->unique();            // UUID untuk QR code
            $table->datetime('waktu_masuk');
            $table->datetime('waktu_keluar')->nullable();
            $table->integer('total_menit')->nullable();
            $table->integer('tarif_per_jam');               // 3000 motor, 5000 mobil
            $table->integer('total_harga')->nullable();
            $table->enum('status', ['aktif', 'selesai', 'dibatalkan'])->default('aktif');
            $table->foreignId('petugas_id')->nullable()->constrained('admin')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiket_parkir');
    }
};

