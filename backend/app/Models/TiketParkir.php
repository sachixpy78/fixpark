<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TiketParkir extends Model
{
    use HasFactory;

    protected $table = 'tiket_parkir';

    protected $fillable = [
        'no_invoice', 'jenis', 'member_id', 'jenis_kendaraan',
        'plat_nomor', 'foto_plat', 'barcode',
        'waktu_masuk', 'waktu_keluar', 'total_menit',
        'tarif_per_jam', 'total_harga', 'status', 'petugas_id',
    ];

    protected function casts(): array
    {
        return [
            'waktu_masuk'  => 'datetime',
            'waktu_keluar' => 'datetime',
        ];
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function petugas()
    {
        return $this->belongsTo(Admin::class, 'petugas_id');
    }

    public function transaksi()
    {
        return $this->hasOne(Transaksi::class, 'tiket_id');
    }
}

