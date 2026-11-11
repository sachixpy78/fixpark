<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('no_member')->unique();      // MBR-20261105-001
            $table->string('nama');
            $table->string('instansi');
            $table->string('email')->unique();
            $table->string('telepon')->nullable();
            $table->date('tanggal_daftar');
            $table->date('masa_aktif');                 // 1 tahun dari tanggal_daftar
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

