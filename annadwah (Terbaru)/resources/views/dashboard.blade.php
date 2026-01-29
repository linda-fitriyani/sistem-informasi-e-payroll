@extends('layouts.layoutmaster')
@section('title', 'Dashboard')

@section('css')
<style>
    /* CSS UNTUK KARTU DASHBOARD ADMIN */
    <style>
    /* CSS UNTUK KARTU DASHBOARD ADMIN (UKURAN DIPERKECIL) */
    body { background-color: #f4f7f9; }
    .dashboard-card {
        background-color: #ffffff;
        border-radius: 8px; /* Sedikit mengurangi radius border */
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        padding: 15px; /* Mengurangi padding dari 20px menjadi 15px */
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-left: 4px solid transparent; /* Border kiri sedikit lebih tipis */
        display: flex; /* Menggunakan flexbox untuk alignment yang lebih baik */
        flex-direction: column;
        justify-content: center;
    }
    .dashboard-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .card-icon {
        font-size: 1.8rem; /* Mengurangi ukuran font ikon */
    }
    .card-icon i {
        font-size: 1.8rem; /* Pastikan ikon di dalamnya juga mengecil */
    }
    .card-title {
        font-size: 0.85rem; /* Mengurangi ukuran font judul */
        font-weight: 500;
        color: #6c757d;
    }
    .card-value {
        font-size: 1.8rem; /* Mengurangi ukuran font nilai */
        font-weight: 600;
        color: #343a40;
    }

    /* Warna border tetap sama */
    .card-primary { border-left-color: #5a99e7; } .card-primary .card-icon { color: #5a99e7; }
    .card-success { border-left-color: #4CAF50; } .card-success .card-icon { color: #4CAF50; }
    .card-warning { border-left-color: #ffab2d; } .card-warning .card-icon { color: #ffab2d; }
    .card-info { border-left-color: #36c2d6; } .card-info .card-icon { color: #36c2d6; }
    .card-danger { border-left-color: #e75a5a; } .card-danger .card-icon { color: #e75a5a; }
    .card-secondary { border-left-color: #8e9aA6; } .card-secondary .card-icon { color: #8e9aA6; }

    /* CSS LAINNYA UNTUK PROFIL KARYAWAN (TIDAK DIUBAH) */
    .profile-card {
        background-color: #fff; border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); padding: 30px;
    }
    .profile-header { text-align: center; margin-bottom: 25px; }
    .profile-header img {
        width: 120px; height: 120px; border-radius: 50%;
        border: 4px solid #0d6efd; object-fit: cover;
    }
    .profile-header h4 { margin-top: 15px; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
    .profile-header p { color: #7f8c8d; }
    .info-item {
        display: flex; justify-content: space-between; padding: 12px 0;
        border-bottom: 1px solid #ecf0f1; font-size: 15px;
    }
    .info-item:last-child { border-bottom: none; }
    .info-label { font-weight: 600; color: #34495e; }
    .info-value { color: #34495e; text-align: right; }
</style>
</style>
@endsection

@section('content')
@include('sweetalert::alert')

<section class="section dashboard">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                {{-- Sapaan umum untuk semua user --}}
                <h4>Selamat Datang, {{ auth()->user()->name }}!</h4>
            </div>
        </div>

        {{-- ====================================================== --}}
        {{-- DIUBAH: Menggunakan @if untuk mengecek role 'admin' --}}
        {{-- ====================================================== --}}
        @if(auth()->user()->hasRole('admin'))
            <div class="row g-4">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dashboard-card card-primary">
                        <div class="card-icon"><i class='bx bxs-user-detail'></i></div>
                        <div class="card-title">Total Karyawan</div>
                        <div class="card-value">{{ $totalKaryawan ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dashboard-card card-success">
                        <div class="card-icon"><i class='bx bxs-briefcase'></i></div>
                        <div class="card-title">Total Jabatan</div>
                        <div class="card-value">{{ $totalJabatan ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dashboard-card card-success">
                        <div class="card-icon"><i class='bx bxs-user-check'></i></div>
                        <div class="card-title">Hadir Hari Ini</div>
                        <div class="card-value">{{ $hadirHariIni ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dashboard-card card-danger">
                        <div class="card-icon"><i class='bx bxs-user-x'></i></div>
                        <div class="card-title">Sakit Hari Ini</div>
                        <div class="card-value">{{ $sakitHariIni ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dashboard-card card-warning">
                        <div class="card-icon"><i class='bx bxs-user-plus'></i></div>
                        <div class="card-title">Izin Hari Ini</div>
                        <div class="card-value">{{ $izinHariIni ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="dashboard-card card-secondary">
                        <div class="card-icon"><i class='bx bxs-user-plus'></i></div>
                        <div class="card-title">Alpha Hari Ini</div>
                        <div class="card-value">{{ $alphaHariIni ?? 0 }}</div>
                    </div>
                </div>
                {{-- Tambahkan kartu lain jika perlu --}}
                  {{-- ====================================================== --}}
               
            </div>
            <div class="row mt-4">
           {{-- TAMBAHAN: Kartu untuk Grafik (Kolom 2) --}}
                {{-- ====================================================== --}}
                <div class="col-lg">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Grafik Absensi Hari Ini</h5>
                            {{-- Elemen Canvas untuk Chart.js --}}
                            <div style="min-height: 350px;">
                                <canvas id="absensiChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {{-- ====================================================== --}}
        {{-- DIUBAH: Menggunakan @elseif untuk mengecek role 'karyawan' --}}
        {{-- ====================================================== --}}
        @elseif(auth()->user()->hasRole('karyawan'))
            @if(isset($karyawan))
            <div class="row d-flex justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="profile-card">
                        <div class="profile-header">
                            <img src="{{ asset('/storage/' . $karyawan->foto) }}" onerror="this.src='{{ asset('assets/img/profile-img.jpg')}}'" alt="Foto Profil">
                              <!--<img src="{{asset('assets/img/profile-img.jpg')}}" alt="Profile" class="rounded-circle">-->
                            <h4>{{ $karyawan->nama }}</h4>
                            <p>{{ $karyawan->jabatan->nama_jabatan ?? 'Jabatan tidak tersedia' }}</p>
                        </div>
                        <div class="profile-body">
                            <div class="info-item">
                                <span class="info-label">NIK</span>
                                <span class="info-value">{{ $karyawan->nik ?? '-' }}</span>
                            </div>
                            <!--<div class="info-item">-->
                            <!--    <span class="info-label">Email</span>-->
                            <!--    <span class="info-value">{{ $karyawan->user->email ?? '-' }}</span>-->
                            <!--</div>-->
                            <!--<div class="info-item">-->
                            <!--    <span class="info-label">No. HP</span>-->
                            <!--    <span class="info-value">{{ $karyawan->no_hp ?? '-' }}</span>-->
                            <!--</div>-->
                            <!--<div class="info-item">-->
                            <!--    <span class="info-label">Tanggal Masuk</span>-->
                            <!--    <span class="info-value">{{ optional($karyawan->tanggal_masuk)->translatedFormat('l, d F Y') ?? '-' }}</span>-->
                            <!--</div>-->
                            <!--<div class="info-item">-->
                            <!--    <span class="info-label">Tanggal Lahir</span>-->
                            <!--    <span class="info-value">{{ optional($karyawan->tanggal_lahir)->translatedFormat('l, d F Y') ?? '-' }}</span>-->
                            <!--</div>-->
                             <div class="info-item">
                                <span class="info-label">Jabatan</span>
                                <span class="info-value"> {{ $karyawan->jabatan->nama_jabatan ?? '-' }}</span>
                            </div>
                            <!--<div class="info-item">-->
                            <!--    <span class="info-label">Agama</span>-->
                            <!--    <span class="info-value">{{ $karyawan->agama ?? '-' }}</span>-->
                            <!--</div>-->
                            <!--<div class="info-item">-->
                            <!--    <span class="info-label">Status Pernikahan</span>-->
                            <!--    <span class="info-value">{{ $karyawan->status_kawin ?? '-' }}</span>-->
                            <!--</div>-->
                             <div class="info-item">
                                <span class="info-label">Status Karyawan</span>
                                <span class="info-value">{{ $karyawan->status_karyawan ?? '-' }}</span>
                            </div>
                            <!--<div class="info-item">-->
                            <!--    <span class="info-label">Alamat</span>-->
                            <!--    <span class="info-value">{{ $karyawan->alamat ?? '-' }}</span>-->
                            <!--</div>-->
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-danger">
                Data profil karyawan tidak ditemukan. Silakan hubungi admin.
            </div>
            @endif

        {{-- ====================================================== --}}
        {{-- DIUBAH: Menggunakan @else untuk role lainnya --}}
        {{-- ====================================================== --}}
        @else
        <div class="alert alert-info">
            Anda tidak memiliki akses ke konten dashboard.
        </div>
        @endif

    </div>
</section>
@endsection


{{-- ====================================================== --}}
{{-- TAMBAHAN: Script untuk merender grafik --}}
{{-- ====================================================== --}}
@push('scripts')
{{-- Pastikan Chart.js sudah di-include di layout master Anda.
     Jika belum, tambahkan: <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> --}}

 <!--<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
<script>
    // Pastikan dokumen sudah siap
    document.addEventListener("DOMContentLoaded", () => {
        // Cek apakah variabel dari controller ada, khusus untuk admin
        @if(auth()->user()->hasRole('admin') && isset($absensiChartData))
            
            // Ambil data dari PHP dan ubah menjadi objek JavaScript
            const absensiData = @json($absensiChartData);

            // Siapkan label dan data untuk chart dari objek
            const labels = Object.keys(absensiData);
            const dataPoints = Object.values(absensiData);
            console.log(labels,dataPoints)
            // Konfigurasi Chart.js
            new Chart(document.getElementById('absensiChart'), {
                type: 'bar', // Tipe grafik: doughnut, pie, bar, dll.
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Karyawan',
                        data: dataPoints,
                        backgroundColor: [
                            '#4CAF50', // Hadir (Hijau)
                            '#e75a5a', // Sakit (Merah)
                            '#ffab2d', // Izin (Kuning)
                            '#8e9aA6'  // Alpha (Abu-abu)
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom', // Posisi legenda
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                        // Untuk Bar Chart, nilainya ada di properti .y
                                        let value = context.parsed.y;

                                        if (value !== null) {
                                            return value + ' orang';
                                        }
                                        return '';
                                    }
                            }
                        }
                    }
                }
            });
        @endif
    });
</script>
@endpush