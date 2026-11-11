<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $fillable = [
        'no_member',
        'nama',
        'instansi',
        'email',
        'telepon',
        'plat_1',
        'plat_2',
        'plat_3',
        'motor_1',
        'motor_2',
        'motor_3',
        'mobil_1',
        'mobil_2',
        'mobil_3',
        'tanggal_daftar',
        'masa_aktif',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_daftar' => 'date',
            'masa_aktif'     => 'date',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    protected $appends = ['is_expired', 'status_label', 'daftar_plat', 'daftar_plat_motor', 'daftar_plat_mobil'];

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->masa_aktif) {
            return false;
        }
        return now()->startOfDay()->gt($this->masa_aktif->startOfDay());
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_expired) {
            return 'expired';
        }
        return $this->status ?? 'aktif';
    }

    public function getDaftarPlatAttribute(): array
    {
        return array_values(array_filter([
            $this->motor_1 ? strtoupper(trim($this->motor_1)) : null,
            $this->motor_2 ? strtoupper(trim($this->motor_2)) : null,
            $this->motor_3 ? strtoupper(trim($this->motor_3)) : null,
            $this->mobil_1 ? strtoupper(trim($this->mobil_1)) : null,
            $this->mobil_2 ? strtoupper(trim($this->mobil_2)) : null,
            $this->mobil_3 ? strtoupper(trim($this->mobil_3)) : null,
        ]));
    }

    public function getDaftarPlatMotorAttribute(): array
    {
        return array_values(array_filter([
            $this->motor_1 ? strtoupper(trim($this->motor_1)) : null,
            $this->motor_2 ? strtoupper(trim($this->motor_2)) : null,
            $this->motor_3 ? strtoupper(trim($this->motor_3)) : null,
        ]));
    }

    public function getDaftarPlatMobilAttribute(): array
    {
        return array_values(array_filter([
            $this->mobil_1 ? strtoupper(trim($this->mobil_1)) : null,
            $this->mobil_2 ? strtoupper(trim($this->mobil_2)) : null,
            $this->mobil_3 ? strtoupper(trim($this->mobil_3)) : null,
        ]));
    }

    public function tikets()
    {
        return $this->hasMany(TiketParkir::class, 'member_id');
    }
}
