<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Parkir</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

  .header {
    background: #b91c1c;
    color: white;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
  }
  .logo-box {
    width: 44px; height: 44px;
    background: rgba(255,255,255,0.18);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; font-weight: 900; color: white;
  }
  .header-text h1 { font-size: 16px; font-weight: 700; margin-bottom: 2px; }
  .header-text p  { font-size: 11px; opacity: .85; }

  .meta { padding: 0 20px 10px; display: flex; gap: 30px; }
  .meta span { color: #555; }
  .meta strong { color: #b91c1c; }

  table { width: 100%; border-collapse: collapse; margin: 0 0 14px 0; }
  thead tr { background: #b91c1c; color: white; }
  thead th { padding: 7px 8px; text-align: left; font-size: 10px; font-weight: 600; }
  tbody tr:nth-child(even) { background: #fef2f2; }
  tbody td { padding: 6px 8px; border-bottom: 1px solid #fecaca; }

  .total-row { background: #7f1d1d !important; color: white; font-weight: 700; }
  .total-row td { border: none; padding: 8px; }

  .footer { text-align: center; color: #aaa; font-size: 9px; margin-top: 10px; padding: 10px; border-top: 1px solid #fecaca; }

  .badge-cash   { background:#fef2f2; color:#b91c1c; padding:2px 6px; border-radius:4px; font-weight:600; }
  .badge-member { background:#eff6ff; color:#1d4ed8; padding:2px 6px; border-radius:4px; font-weight:600; }
</style>
</head>
<body>

<div class="header">
  <div class="logo-box">P</div>
  <div class="header-text">
    <h1>E-Parkir Plaza Andalas</h1>
    <p>Laporan Transaksi Parkir &mdash; {{ $namaBulan }} {{ $tahun }}</p>
  </div>
</div>

<div class="meta">
  <div><span>Petugas: </span><strong>{{ $namaPetugas }}</strong></div>
  <div><span>Periode: </span><strong>{{ $namaBulan }} {{ $tahun }}</strong></div>
  <div><span>Total Tiket: </span><strong>{{ count($data) }}</strong></div>
  <div><span>Total Pendapatan: </span><strong>Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</strong></div>
</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>No Invoice</th>
      <th>Plat</th>
      <th>Kendaraan</th>
      <th>Waktu Masuk</th>
      <th>Waktu Keluar</th>
      <th>Durasi</th>
      <th>Metode</th>
      <th>Total</th>
      <th>Petugas</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($data as $i => $row)
    <tr>
      <td>{{ $i + 1 }}</td>
      <td>{{ $row['no_invoice'] }}</td>
      <td><strong>{{ $row['plat_nomor'] ?? '-' }}</strong></td>
      <td style="text-transform:capitalize">{{ $row['jenis_kendaraan'] }}</td>
      <td>{{ $row['waktu_masuk'] }}</td>
      <td>{{ $row['waktu_keluar'] }}</td>
      <td>{{ $row['durasi'] }}</td>
      <td>
        @if($row['metode'] === 'member')
          <span class="badge-member">Member</span>
        @else
          <span class="badge-cash">Cash</span>
        @endif
      </td>
      <td>Rp {{ number_format($row['total_harga'], 0, ',', '.') }}</td>
      <td>{{ $row['petugas'] }}</td>
    </tr>
    @empty
    <tr><td colspan="10" style="text-align:center;padding:20px;color:#aaa;">Tidak ada data</td></tr>
    @endforelse
    <tr class="total-row">
      <td colspan="8">TOTAL PENDAPATAN</td>
      <td>Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
      <td></td>
    </tr>
  </tbody>
</table>

<div class="footer">
  Dicetak pada {{ now()->format('d/m/Y H:i') }} &bull; E-Parkir Plaza Andalas &copy; {{ now()->year }}
</div>

</body>
</html>

