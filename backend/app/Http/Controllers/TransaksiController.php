<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    // GET /api/transaksi
    public function index(Request $request)
    {
        $query = Transaksi::with(['tiket.member', 'petugas'])->latest();

        if ($request->tanggal_dari) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }
        if ($request->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $request->tanggal_sampai);
        }
        if ($request->petugas_id) {
            $query->where('petugas_id', $request->petugas_id);
        }

        return response()->json($query->get()->map(fn($t) => $this->formatTransaksi($t)));
    }

    // GET /api/laporan/ringkasan
    public function ringkasan(Request $request)
    {
        $query = Transaksi::with('petugas')
            ->selectRaw('petugas_id, COUNT(*) as total_transaksi, SUM(total_harga) as total_pendapatan')
            ->groupBy('petugas_id');

        if ($request->tanggal_dari) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }
        if ($request->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $request->tanggal_sampai);
        }

        return response()->json($query->get()->map(fn($r) => [
            'petugas_id'       => $r->petugas_id,
            'petugas_nama'     => $r->petugas?->username ?? '-',
            'total_transaksi'  => $r->total_transaksi,
            'total_pendapatan' => $r->total_pendapatan,
        ]));
    }

    private function formatTransaksi(Transaksi $t): array
    {
        return [
            'id'              => $t->id,
            'created_at'      => $t->created_at->format('Y-m-d H:i:s'),
            'no_invoice'      => $t->tiket->no_invoice,
            'jenis_kendaraan' => $t->tiket->jenis_kendaraan,
            'plat_nomor'      => $t->tiket->plat_nomor,
            'waktu_masuk'     => $t->tiket->waktu_masuk?->format('Y-m-d H:i:s'),
            'waktu_keluar'    => $t->tiket->waktu_keluar?->format('Y-m-d H:i:s'),
            'total_menit'     => $t->tiket->total_menit,
            'jenis_member'    => $t->tiket->jenis,
            'member_nama'     => $t->tiket->member?->nama,
            'total_harga'     => $t->total_harga,
            'nominal_bayar'   => $t->nominal_bayar,
            'kembalian'       => $t->kembalian,
            'metode'          => $t->metode,
            'petugas'         => $t->petugas?->username ?? '-',
        ];
    }
}

