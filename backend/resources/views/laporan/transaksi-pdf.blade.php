<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi Parkir</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 20px; color: #1e293b; }
        .header p { margin: 5px 0 0; color: #64748b; }
        
        .stats-container { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .stat-box { 
            background: #f1f5f9; border: 2px solid #cbd5e1; border-radius: 8px; 
            padding: 12px; text-align: center; width: 18%; 
        }
        .stat-box .label { font-size: 9px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .stat-box .value { font-size: 18px; font-weight: bold; color: #1e293b; margin-top: 5px; }
        .stat-box.highlight .value { color: #2563eb; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #3b82f6; color: white; padding: 10px; text-align: left; font-size: 9px; text-transform: uppercase; font-weight: bold; }
        td { border-bottom: 1px solid #e2e8f0; padding: 8px; font-size: 9px; }
        tr:nth-child(even) { background-color: #f8fafc; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { background-color: #dbeafe; font-weight: bold; font-size: 11px; color: #1e40af; }
        
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 8px; font-weight: bold; }
        .badge-member { background-color: #dbeafe; color: #1e40af; }
        .badge-non { background-color: #f1f5f9; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN TRANSAKSI PARKIR</h2>
        <p>Periode: {{ \Carbon\Carbon::parse($tanggalDari)->format('d F Y') }} - {{ \Carbon\Carbon::parse($tanggalSampai)->format('d F Y') }}</p>
    </div>

    <div class="stats-container">
        <div class="stat-box">
            <div class="label">Total Transaksi</div>
            <div class="value">{{ $stats['total'] }}</div>
        </div>
        <div class="stat-box highlight">
            <div class="label">Total Pendapatan</div>
            <div class="value">Rp {{ number_format($stats['pendapatan'], 0, ',', '.') }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Sepeda Motor</div>
            <div class="value">{{ $stats['motor'] }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Mobil</div>
            <div class="value">{{ $stats['mobil'] }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Member</div>
            <div class="value">{{ $stats['member'] }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="12%">ID Transaksi</th>
                <th width="8%">Kendaraan</th>
                <th width="10%">Plat Nomor</th>
                <th width="14%">Waktu Masuk</th>
                <th width="14%">Waktu Keluar</th>
                <th class="text-center" width="8%">Durasi</th>
                <th class="text-center" width="10%">Metode</th>
                <th class="text-right" width="19%">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transaksi as $index => $t)
                @php
                    $tiket = $t->tiket; // Simpan ke variabel agar lebih rapi
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>#{{ $t->id }}</td>
                    <td>{{ ucfirst($tiket->jenis_kendaraan ?? '-') }}</td>
                    <td>{{ $tiket->plat_nomor ?? '-' }}</td>
                    <td>{{ $tiket->waktu_masuk ? \Carbon\Carbon::parse($tiket->waktu_masuk)->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $tiket->waktu_keluar ? \Carbon\Carbon::parse($tiket->waktu_keluar)->format('d/m/Y H:i') : '-' }}</td>
                    <td class="text-center">
                        @if($tiket && $tiket->total_menit)
                            {{ floor($tiket->total_menit / 60) }}j {{ $tiket->total_menit % 60 }}m
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ ($t->metode == 'member' || ($tiket && $tiket->jenis_member == 'member')) ? 'badge-member' : 'badge-non' }}">
                            {{ strtoupper($t->metode ?? 'CASH') }}
                        </span>
                    </td>
                    <td class="text-right font-bold">Rp {{ number_format($t->total_harga ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 20px; color: #64748b;">
                    Tidak ada data transaksi pada periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($transaksi->count() > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="8" class="text-right">TOTAL PENDAPATAN</td>
                <td class="text-right">Rp {{ number_format($stats['pendapatan'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div style="margin-top: 30px; text-align: right; font-size: 9px; color: #94a3b8;">
        Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }} WIB
    </div>
</body>
</html>