<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('no_member');
            $table->string('nama_member');
            $table->string('instansi')->nullable();
            $table->date('masa_aktif_lama')->nullable();
            $table->date('masa_aktif_baru');
            $table->decimal('nominal_bayar', 15, 2)->default(150000);
            $table->string('petugas')->nullable(); // nama petugas yang memproses
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_renewals');
    }
};
