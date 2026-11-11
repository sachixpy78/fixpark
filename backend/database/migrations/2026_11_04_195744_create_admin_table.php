<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('gambar')->nullable();
            $table->enum('level', ['admin', 'petugas']);
            $table->integer('login_attempts')->default(0);
            $table->timestamp('last_login_at')->nullable();

            // lupa password (OTP via gmail)
            $table->string('otp')->nullable();               // disimpan ter-hash
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};