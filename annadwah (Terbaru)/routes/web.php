<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\ArtikelController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\HariLibur;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\KasbonController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PotonganController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransaksiStokController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChangePasswordControllerUser;
use App\Models\Gaji;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/test-email', [DashboardController::class, 'testEmail']);
Route::get('/login', function () {
    return view('auth.login');
});

Route::get('/register', function () {
    return view('auth.register');
});

Auth::routes();
Route::group(['middleware' => ['auth']], function () {
    // Daftar route khusus admin

    Route::resource('users', UserController::class);
    Route::resource('karyawan', KaryawanController::class);

    // Endpoint API untuk dekripsi banyak field dengan kunci admin
    // Route::post('/decrypt-fields-with-key', [KaryawanController::class, 'decryptMultipleFields'])->name('decrypt.multiple.fields');
    // Route untuk memproses kunci admin (POST request)
    Route::post('/karyawan/decrypt', [KaryawanController::class, 'decryptIndex'])->name('karyawan.decrypt');

    Route::post('/karyawan/process-admin-key', [KaryawanController::class, 'processAdminKey'])->name('karyawan.processAdminKey');

    // Route untuk menghapus kunci admin dari sesi (GET request)
    Route::get('/karyawan/remove-admin-key', [KaryawanController::class, 'removeAdminKey'])->name('karyawan.removeAdminKey');


    Route::resource('absensi', AbsensiController::class);
    Route::get('/absensi/{tanggal}/edit', [AbsensiController::class, 'edit'])->name('absensi.edit');
    Route::put('/absensi/{tanggal}', [AbsensiController::class, 'update'])->name('absensi.update');
    Route::get('/rekapan-absensi', [AbsensiController::class, 'rekapabsensi'])->name('absensi.rekap');
    Route::get('/grafik/{karyawan_id}', [AbsensiController::class, 'grafikKehadiran'])->name('grafik.kehadiran');
    Route::get('/export-absensi', [AbsensiController::class, 'cetakPdf'])->name('absensi.pdf');
    Route::post('/export-absensi', [AbsensiController::class, 'exportData'])->name('absensi.cetaknow');
    Route::get('/laporan-absensi-view', [AbsensiController::class, 'laporanView'])->name('laporan.absensi.view');
    Route::post('/laporan-absensi', [AbsensiController::class, 'laporanCetak'])->name('laporan.absensi.cetak');

    // Jabatan
    Route::resource('jabatan', JabatanController::class);
    // decrypt
    Route::post('/jabatan/decrypt', [JabatanController::class, 'decryptIndex'])->name('jabatan.decrypt');

    // Potongan
    Route::resource('potongan', PotonganController::class);

    // bonus
    Route::get('/bonus', [BonusController::class, 'index'])->name('bonus.index');
    Route::get('/bonus/create', [BonusController::class, 'create'])->name('bonus.create');
    Route::post('/bonus', [BonusController::class, 'store'])->name('bonus.store');
    Route::get('/bonus/{bonus}', [BonusController::class, 'show'])->name('bonus.show');
    Route::get('/bonus/{bonus}/edit', [BonusController::class, 'edit'])->name('bonus.edit');
    Route::put('/bonus/{bonus}', [BonusController::class, 'update'])->name('bonus.update');
    Route::delete('/bonus/{bonus}/destroy', [BonusController::class, 'destroy'])->name('bonus.destroy');
    // Gaji
    // Route::resource('gaji', GajiController::class);
    Route::post('/gaji/generate', [GajiController::class, 'generateGaji'])->name('gaji.generate');
    // decrypt
    Route::post('/gaji/decrypt', [GajiController::class, 'decryptGaji'])->name('gaji.decrypt');
    // kirim email
    Route::post('/slip-email', [GajiController::class, 'sendEmail'])->name('gaji.email');

    Route::get('/slip-gaji-karyawan/{nomor_slip}', [GajiController::class, 'showSlipKaryawan'])->name('gaji.showKaryawan');

    // Route untuk mengunduh PDF slip gaji
    Route::get('/slip-gaji/{nomor_slip}/download-pdf', [GajiController::class, 'downloadSlipGajiPdf'])->name('gaji.downloadSlipGajiPdf');
    Route::get('/slip-gaji/karyawan', [GajiController::class, 'laporanSlip'])->name('slipgaji.cetak');
    Route::post('/slip-gaji/karyawan/cetak', [GajiController::class, 'cetakSlipGaji'])->name('laporan.slipgaji.cetak');


    Route::get('/gaji', [GajiController::class, 'index'])->name('gaji.index');
    Route::get('/gaji/{bulan}/{tahun}', [GajiController::class, 'show'])->name('gaji.show');
    Route::get('/gaji/{bulan}/{tahun}/edit', [GajiController::class, 'edit'])->name('gaji.edit');
    Route::delete('/gaji/{bulan}/{tahun}', [GajiController::class, 'destroy'])->name('gaji.destroy');
    Route::get('/laporan-gaji/view', [GajiController::class, 'laporanView'])->name('laporan.gaji.view');
    Route::post('/laporan-gaji', [GajiController::class, 'laporanCetak'])->name('laporan.gaji.cetak');


    //harilibur
    Route::resource('harilibur', HariLibur::class);
});
Route::group(
    ['middleware' => ['auth']],
    function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/riwayat-gaji-karyawan', [KaryawanController::class, 'riwayatGaji'])->name('gaji.karyawan');
        
        Route::get('/ganti-password', [ChangePasswordControllerUser::class, 'showChangePasswordForm'])->name('password.change.form');
        Route::post('/ganti-password', [ChangePasswordControllerUser::class, 'changePassword'])->name('password.change.update');
        }
    );


// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');