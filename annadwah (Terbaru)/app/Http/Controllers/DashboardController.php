<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\RawMaterial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
class DashboardController extends Controller
{
    public function testEmail()
    {
        Mail::raw('Ini adalah email uji coba.', function ($message) {
            $message->to('test@gmail.com')->subject('Uji Coba Mailtrap');
        });
        return "Email uji coba telah dikirim (ke Gmail).";
    }

    
    public function index()
    {
     $user = auth()->user();

        // ======================================================
        // JIKA USER YANG LOGIN MEMILIKI ROLE 'ADMIN'
        // ======================================================
        
        if ($user->hasRole('admin')) {
            // Data untuk kartu statistik (biarkan seperti apa adanya)
            $totalKaryawan = Karyawan::count();
            $totalJabatan = Jabatan::count();
            $today = Carbon::today();
            $hadirHariIni = Absensi::whereDate('tanggal', $today)->where('status_kehadiran', 'Hadir')->count();
            $izinHariIni = Absensi::whereDate('tanggal', $today)->where('status_kehadiran', 'Izin')->count();
            $sakitHariIni = Absensi::whereDate('tanggal', $today)->where('status_kehadiran', 'Sakit')->count();
            $alphaHariIni = Absensi::whereDate('tanggal', $today)->where('status_kehadiran', 'Alpha')->count(); // Tambahkan Alpha untuk kartu jika perlu

            // ======================================================
            // TAMBAHAN: Menyiapkan data untuk Grafik Absensi
            // ======================================================
            $statuses = ['Hadir', 'Sakit', 'Izin', 'Alpha'];
            
            // Ambil data dari DB dalam satu query efisien
            $absensiCounts = Absensi::whereDate('tanggal', $today)
                ->select('status_kehadiran', DB::raw('count(*) as total'))
                ->groupBy('status_kehadiran')
                ->pluck('total', 'status_kehadiran');
            
            // Buat array data chart, pastikan semua status ada (meskipun nilainya 0)
            $absensiChartData = [];
            foreach ($statuses as $status) {
                $absensiChartData[$status] = $absensiCounts->get($status, 0);
            }
            // Hasilnya akan seperti: ['Hadir' => 10, 'Sakit' => 1, 'Izin' => 2, 'Alpha' => 0]

            // Kirim semua data (statistik & chart) ke view
            // dd($absensiChartData);
            return view('dashboard', compact(
                'totalKaryawan',
                'totalJabatan',
                'hadirHariIni',
                'izinHariIni',
                'sakitHariIni',
                'alphaHariIni', // <-- Variabel baru untuk Alpha (opsional untuk kartu)
                'absensiChartData' // <-- Variabel BARU untuk grafik
            ));
        }

        // ======================================================
        // JIKA USER YANG LOGIN MEMILIKI ROLE 'KARYAWAN'
        // ======================================================
        elseif ($user->hasRole('karyawan')) {
            
            // 1. Ambil data karyawan yang terhubung dengan user ini.
            // Gunakan 'with()' untuk eager loading agar query lebih efisien.
            $karyawan = Karyawan::where('user_id', $user->id)->with('jabatan', 'user')->first();

            // 2. Jika data karyawan tidak ditemukan, berikan pesan error.
            if (!$karyawan) {
                return view('dashboard')->with('error', 'Data profil karyawan Anda tidak ditemukan. Silakan hubungi admin.');
            }

            try {
                if ($karyawan->nik) {
                    $karyawan->nik = Crypt::decryptString($karyawan->nik);
                }
            } catch (DecryptException $e) {
                $karyawan->nik = '[Gagal Memuat NIP]';
            }
           

            // 4. Kirim objek karyawan yang datanya sudah siap tampil ke view.
            return view('dashboard', compact('karyawan'));
        }
        
        // ======================================================
        // JIKA USER TIDAK MEMILIKI ROLE DI ATAS
        // ======================================================
        else {
            // Tampilkan dashboard default atau berikan pesan.
            return view('dashboard')->with('message', 'Selamat datang di dashboard.');
        }
    }
}
