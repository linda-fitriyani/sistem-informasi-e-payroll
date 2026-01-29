@extends('layouts.templatepdf')

@section('content')

   <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h3, .header h4 {
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
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
        tfoot tr td {
            font-weight: bold;
        }
    </style>
    <div class="header">
        <h3>LAPORAN REKAPITULASI GAJI KARYAWAN</h3>
        <h4>PERIODE: {{ $namaBulan }} {{ $tahun }}</h4>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 10%;">NIK</th>
                <th>Nama Karyawan</th>
                <th style="width: 15%;">Jabatan</th>
                <th style="width: 12%;">Gaji Pokok</th>
                <th style="width: 12%;">Tunjangan</th>
                <th style="width: 12%;">Total Bonus</th>
                <th style="width: 12%;">Total Potongan</th>
                <th style="width: 12%;">Gaji Bersih</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($gajis as $gaji)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-left">{{ $gaji->karyawan->nik ?? 'N/A' }}</td>
                <td class="text-left">{{ $gaji->karyawan->nama ?? 'N/A' }}</td>
                <td class="text-left">{{ $gaji->karyawan->jabatan->nama_jabatan ?? 'N/A' }}</td>
                <td class="text-right">
                    {{ is_numeric($gaji->gaji_pokok) ? 'Rp ' . number_format($gaji->gaji_pokok, 0, ',', '.') : $gaji->gaji_pokok }}
                </td>
                <td class="text-right">
                    {{ is_numeric($gaji->tunjangan) ? 'Rp ' . number_format($gaji->tunjangan, 0, ',', '.') : $gaji->tunjangan }}
                </td>
                 <td class="text-right">
                    {{ is_numeric($gaji->total_bonus) ? 'Rp ' . number_format($gaji->total_bonus, 0, ',', '.') : $gaji->total_bonus }}
                </td>
                <td class="text-right">
                    {{ is_numeric($gaji->total_potongan) ? 'Rp ' . number_format($gaji->total_potongan, 0, ',', '.') : $gaji->total_potongan }}
                </td>

                <td class="text-right">
                    {{ is_numeric($gaji->gaji_bersih) ? 'Rp ' . number_format($gaji->gaji_bersih, 0, ',', '.') : $gaji->gaji_bersih }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="text-right"><strong>TOTAL KESELURUHAN GAJI BERSIH</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($grandTotalGajiBersih, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

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