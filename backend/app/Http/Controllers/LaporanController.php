<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function cetakPdf(Request $request)
    {
        $tanggalDari = $request->query('tanggal_dari', now()->toDateString());
        $tanggalSampai = $request->query('tanggal_sampai', now()->toDateString());

        // Ambil data transaksi DAN relasi tiket-nya sekaligus
        $transaksi = Transaksi::with('tiket')
            ->whereBetween('created_at', [
                $tanggalDari . ' 00:00:00',
                $tanggalSampai . ' 23:59:59'
            ])
            ->orderByDesc('created_at')
            ->get();

        // Hitung statistik berdasarkan data dari relasi tiket
        $stats = [
            'total' => $transaksi->count(),
            'pendapatan' => $transaksi->sum('total_harga'),
            'motor' => $transaksi->filter(fn($t) => $t->tiket && strtolower($t->tiket->jenis_kendaraan) === 'motor')->count(),
            'mobil' => $transaksi->filter(fn($t) => $t->tiket && strtolower($t->tiket->jenis_kendaraan) === 'mobil')->count(),
            'member' => $transaksi->filter(fn($t) => $t->metode === 'member' || ($t->tiket && $t->tiket->jenis_member === 'member'))->count(),
        ];

        $pdf = Pdf::loadView('laporan.transaksi-pdf', compact('transaksi', 'stats', 'tanggalDari', 'tanggalSampai'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Parkir_' . $tanggalDari . '_sd_' . $tanggalSampai . '.pdf');
    }
}