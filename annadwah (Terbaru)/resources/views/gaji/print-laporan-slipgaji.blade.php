@extends('layouts.templatepdf')

@section('content')

    <style>
        /* Definisi CSS dasar untuk PDF */
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Font yang mendukung karakter unicode, penting untuk Dompdf */
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box; /* Agar padding dihitung dalam lebar */
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            position: relative; /* Penting: Jadikan .header sebagai konteks posisi untuk logo absolut */
            height: 60px; /* Beri tinggi minimum pada header agar logo punya tempat */
            padding-top: 10px; /* Sedikit padding atas untuk konten header */
        }
        .header .logo {
            position: absolute; /* Posisikan logo secara absolut */
            left: 0; /* Letakkan di paling kiri */
            top: 0; /* Letakkan di paling atas dari .header */
            max-height: 50px; /* Batasi tinggi logo */
            width: auto; /* Biarkan lebar menyesuaikan proporsi */
        }
        .header-content {
            text-align: center; /* Pusatkan teks di dalam div ini */
            /* Tambahkan padding kiri agar teks tidak tumpang tindih dengan logo */
            /* Sesuaikan nilai 70px berdasarkan lebar logo + spasi yang diinginkan */
            padding-left: 70px;
            padding-right: 20px; /* Opsional: padding kanan untuk keseimbangan visual */
        }
        .header h1 {
            color: #0056b3;
            margin: 0;
            font-size: 18px;
        }
        .header p {
            font-size: 11px;
            color: #555;
            margin-top: 5px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            color: #34495e;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }
        .info-table, .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table td, .detail-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 35%; /* Atur lebar label */
            color: #2c3e50;
        }
        .detail-table td:first-child {
            width: 65%; /* Atur lebar label */
            color: #2c3e50;
        }
        .detail-table td:last-child {
            text-align: right;
            font-weight: bold;
            color: #34495e;
        }
        .info-item-indent {
            padding-left: 20px; /* Indentasi untuk rincian bonus/potongan */
        }
        .total-gaji {
            border-top: 2px solid #eee;
            margin-top: 15px;
            padding-top: 10px;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #28a745;
        }
        .footer-note {
            font-size: 9px;
            color: #777;
            text-align: center;
            margin-top: 25px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h3, .header h4 {
            margin: 0;
        }
        .signature-section {
            margin-top: 50px;
            width: 300px;
            float: right;
            text-align: center;
        }
        .signature-section .signature-line {
            margin-top: 70px;
            border-top: 1px solid #333;
        }
    </style>
  
    <div class="container">
     
        <div class="section-title">Informasi Karyawan</div>
        <table class="info-table">
            <tr>
                <td>Nama Karyawan:</td>
                <td>{{ $gaji->karyawan->nama ?? $gaji->karyawan->nama }}</td>
            </tr>
            <tr>
                <td>NIK:</td>
                <td>{{ $gaji->karyawan->nik ?? $gaji->karyawan->nik }}</td>
            </tr>
            <tr>
                <td>Status Karyawan:</td>
                <td>{{ $gaji->karyawan->status_karyawan ?? 'Kontrak' }}</td>
            </tr>
            <tr>
                <td>Jabatan:</td>
                <td>{{ $gaji->karyawan->jabatan->nama_jabatan ?? '-' }}</td>
            </tr>
          
        </table>

        <div class="section-title">Rincian Gaji</div>
        <table class="detail-table">
            <tr>
                <td>Gaji Pokok:</td>
                <td>Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan:</td>
                <td>Rp {{ number_format($gaji->tunjangan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Bonus:</td>
                <td>Rp {{ number_format($gaji->total_bonus, 0, ',', '.') }}</td>
            </tr>
            @if($gaji->gajiBonuses->isNotEmpty())
                <tr>
                    <td colspan="2" style="font-weight: normal; padding-top: 5px;">Rincian Bonus:</td>
                </tr>
                @foreach ($gaji->gajiBonuses as $bonusItem)
                    <tr>
                        <td class="info-item-indent">- {{ $bonusItem->bonus->nama_bonus ?? '[Bonus Tidak Ditemukan]' }}:</td>
                        <td>Rp {{ number_format($bonusItem->jumlah_bonus, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </table>

        <div class="section-title">Potongan</div>
        <table class="detail-table">
            <tr>
                <td>Total Potongan:</td>
                <td>Rp {{ number_format($gaji->total_potongan, 0, ',', '.') }}</td>
            </tr>
            @if($gaji->gajiPotongans->isNotEmpty())
                <tr>
                    <td colspan="2" style="font-weight: normal; padding-top: 5px;">Rincian Potongan:</td>
                </tr>
                @foreach ($gaji->gajiPotongans as $potonganItem)
                    <tr>
                        <td class="info-item-indent">- {{ $potonganItem->potongan->nama_potongan ?? '[Potongan Tidak Ditemukan]' }}:</td>
                        <td>Rp {{ number_format($potonganItem->jumlah_potongan, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </table>

        <div class="total-gaji">
            Gaji Bersih: Rp {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}
        </div>

        <div class="footer-note">
            Slip gaji ini berlaku sebagai bukti pembayaran dan dibuat secara otomatis oleh sistem pada tanggal {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}.
            <br>Mohon laporkan ke bagian HRD jika ada ketidaksesuaian.
        </div>
    </div>
    
      <div class="signature-section">
        <p>Bekasi, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
        <p>Ketua Yayasan</p>
        <br>
        <br>
        <br>
        <br>
        <br>
        <div >(_________________ )</div>
    </div>
@endsection