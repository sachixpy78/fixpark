<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberRenewal extends Model
{
    protected $table = 'member_renewals';

    protected $fillable = [
        'member_id',
        'no_member',
        'nama_member',
        'instansi',
        'masa_aktif_lama',
        'masa_aktif_baru',
        'nominal_bayar',
        'petugas',
    ];

    protected function casts(): array
    {
        return [
            'masa_aktif_lama' => 'date',
            'masa_aktif_baru' => 'date',
            'nominal_bayar'   => 'decimal:2',
            'created_at'      => 'datetime',
        ];
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
