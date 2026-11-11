<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // password otomatis ter-hash lewat cast 'hashed' di model
        Admin::updateOrCreate(['email' => 'kozumechibi@gmail.com'], [
            'username' => 'ayam',
            'password' => 'ayam12345',
            'level'    => 'admin',
        ]);

        Admin::updateOrCreate(['email' => 'petugas1@gmail.com'], [
            'username' => 'petugas1',
            'password' => 'petugas12345',
            'level'    => 'petugas',
        ]);
    }
}