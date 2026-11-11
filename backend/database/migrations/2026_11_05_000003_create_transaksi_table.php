<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tiket_parkir')->cascadeOnDelete();
            $table->integer('total_harga');
            $table->integer('nominal_bayar');
            $table->integer('kembalian');
            $table->enum('metode', ['cash', 'member']);     // member = gratis
            $table->foreignId('petugas_id')->nullable()->constrained('admin')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};

