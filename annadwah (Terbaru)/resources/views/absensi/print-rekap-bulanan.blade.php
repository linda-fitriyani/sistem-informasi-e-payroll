@extends('layouts.templatepdf')

@section('content')

    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h3, .header h4 {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #999;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        td {
            vertical-align: top;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>

    <div class="container">
        <div class="header">
            <h3>REKAPITULASI ABSENSI KARYAWAN</h3>
            <h4>Bulan: {{ $namaBulan }} {{ $tahun }}</h4>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 15%;">NIK</th>
                    <th>Nama Karyawan</th>
                    <th style="width: 20%;">Jabatan</th>
                    <th style="width: 8%;">Hadir</th>
                    <th style="width: 8%;">Sakit</th>
                    <th style="width: 8%;">Izin</th>
                    <th style="width: 8%;">Alpha</th>
                </tr>
            </thead>
            <tbody>
                @php($no = 1)
                @forelse ($rekapDataFinal as $data)
                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>{{ $data['nik'] }}</td>
                        <td>{{ $data['nama'] }}</td>
                        <td>{{ $data['jabatan'] }}</td>
                        <td class="text-center">{{ $data['jumlah_hadir'] }}</td>
                        <td class="text-center">{{ $data['jumlah_sakit'] }}</td>
                        <td class="text-center">{{ $data['jumlah_izin'] }}</td>
                        <td class="text-center">{{ $data['jumlah_alpha'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada data untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection