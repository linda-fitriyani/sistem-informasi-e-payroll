<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Bonus;
use App\Models\Gaji;
use App\Models\GajiBonus;
use App\Models\GajiPotongan;
use App\Models\HariLibur;
use App\Models\Karyawan;
use App\Models\Potongan;
use App\Models\SlipGaji;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection; 

class GajiController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    public function generateGaji(Request $request)
    {
        $request->validate([
            'bulan' => 'required|numeric|min:1|max:12',
            'tahun' => 'required|numeric|min:2020',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();

        $hariLibur = HariLibur::whereBetween('tanggal', [$startDate, $endDate])
            ->pluck('tanggal')
            ->map(fn($tgl) => $tgl->format('Y-m-d'))
            ->toArray();

        $potonganTetapList = Potongan::where('status', 'Aktif')->where('otomatis', 1)->get();
        $potonganAbsensiMaster = Potongan::where('nama_potongan', 'Absensi')->first();
        $bonusList = Bonus::where('status', 'Aktif')->where('otomatis', 1)->get();

        $karyawans = Karyawan::with('jabatan')->where('status_karyawan', 'Aktif')->get();

        foreach ($karyawans as $karyawan) {
            $gajiPokok = Crypt::decryptString($karyawan->jabatan->gaji_pokok)  ?? 0;
            $tunjangan = Crypt::decryptString($karyawan->jabatan->tunjangan_jabatan) ?? 0;


            // Calculate actual working days
            $hariKerja = $this->calculateWorkingDays($startDate, $endDate, $hariLibur);
            $gajiPerHari = $hariKerja > 0 ? $gajiPokok / $hariKerja : 0;

            // Calculate bonus
            list($totalBonus, $gajiBonuses) = $this->calculateBonus($karyawan, $gajiPokok, $bonusList, $startDate, $tahun);

            // Calculate deductions
            list($totalPotongan, $gajiPotongans) = $this->calculateDeductions(
                $karyawan,
                $gajiPokok,
                $gajiPerHari,
                $potonganTetapList,
                $potonganAbsensiMaster,
                $startDate,
                $tahun
            );

            $gajiBersih = $gajiPokok + $tunjangan + $totalBonus - $totalPotongan;

            // Save to 'gaji' table
            $gaji = Gaji::create([
                'karyawan_id' => $karyawan->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'gaji_pokok' => Crypt::encryptString(round($gajiPokok)),
                'tunjangan' => Crypt::encryptString(round($tunjangan)),
                'total_potongan' => Crypt::encryptString($totalPotongan),
                'total_bonus' => Crypt::encryptString($totalBonus),
                'gaji_bersih' => Crypt::encryptString($gajiBersih),
                'status' => 'Draft',
                'tanggal_input' => now(),
            ]);

            // Save deductions to 'gaji_potongans' table
            foreach ($gajiPotongans as $gp) {
                GajiPotongan::create([
                    'gaji_id' => $gaji->id,
                    'potongan_id' => $gp['potongan_id'],
                    'jumlah_potongan' => $gp['jumlah_potongan'],
                ]);
            }

            // Save bonuses to 'gaji_bonuses' table
            foreach ($gajiBonuses as $gb) {
                GajiBonus::create([
                    'gaji_id' => $gaji->id,
                    'bonus_id' => $gb['bonus_id'],
                    'jumlah_bonus' => $gb['jumlah_bonus'],
                ]);
            }

            // Save slip gaji
            SlipGaji::create([
                'gaji_id' => $gaji->id,
                'nomor_slip' => strtoupper('SLIP-' . Str::random(8)),
                'status' => 'Belum Dikirim',
            ]);
        }

        return redirect()->back()->with('success', 'Gaji berhasil digenerate untuk bulan ' . $bulan . ' ' . $tahun);
    }

    /**
     * Calculate actual working days excluding Sundays and holidays.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $hariLibur
     * @return int
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate, array $hariLibur): int
    {
        $hariKerja = 0;
        $tanggal = $startDate->copy();
        while ($tanggal <= $endDate) {
            if (!$tanggal->isSunday() && !in_array($tanggal->format('Y-m-d'), $hariLibur)) {
                $hariKerja++;
            }
            $tanggal->addDay();
        }
        return $hariKerja;
    }

    /**
     * Calculate bonus for a given employee.
     *
     * @param Karyawan $karyawan
     * @param float $gajiPokok
     * @param \Illuminate\Database\Eloquent\Collection $bonusList
     * @param Carbon $startDate
     * @param int $tahun
     * @return array [totalBonus, gajiBonuses]
     */
    private function calculateBonus(Karyawan $karyawan, float $gajiPokok, $bonusList, Carbon $startDate, int $tahun): array
    {
        $totalBonus = 0;
        $gajiBonuses = [];

        $jumlahTidakHadir = $this->countAbsence($karyawan->id, $startDate, $tahun);

        foreach ($bonusList as $bonus) {
            $jumlahBonus = 0;

            // Apply bonus discipline rule: no 'Disiplin' bonus if more than 3 absences
            if ($bonus->nama_bonus === 'Disiplin' && $jumlahTidakHadir > 3) {
                continue; // Skip this bonus
            }

            if ($bonus->tipe === 'nominal') {
                $jumlahBonus = $bonus->nilai;
            } elseif ($bonus->tipe === 'persentase') {
                $jumlahBonus = ($gajiPokok * $bonus->nilai) / 100;
            }

            $jumlahBonus = round($jumlahBonus);

            if ($jumlahBonus > 0) {
                $totalBonus += $jumlahBonus;
                $gajiBonuses[] = [
                    'bonus_id' => $bonus->id,
                    'jumlah_bonus' => $jumlahBonus,
                ];
            }
        }
        return [$totalBonus, $gajiBonuses];
    }

    /**
     * Calculate deductions for a given employee.
     *
     * @param Karyawan $karyawan
     * @param float $gajiPokok
     * @param float $gajiPerHari
     * @param \Illuminate\Database\Eloquent\Collection $potonganTetapList
     * @param Potongan|null $potonganAbsensiMaster
     * @param Carbon $startDate
     * @param int $tahun
     * @return array [totalPotongan, gajiPotongans]
     */
    private function calculateDeductions(
        Karyawan $karyawan,
        float $gajiPokok,
        float $gajiPerHari,
        $potonganTetapList,
        ?Potongan $potonganAbsensiMaster,
        Carbon $startDate,
        int $tahun
    ): array {
        $totalPotonganTetap = 0;
        $gajiPotongans = [];

        foreach ($potonganTetapList as $potongan) {
            $jumlahPotongan = 0;
            if ($potongan->tipe === 'nominal') {
                $jumlahPotongan = $potongan->nilai;
            } elseif ($potongan->tipe === 'persentase') {
                $jumlahPotongan = ($gajiPokok * $potongan->nilai) / 100;
            }



            $jumlahPotongan = round($jumlahPotongan);

            if ($jumlahPotongan > 0) {
                $totalPotonganTetap += $jumlahPotongan;
                $gajiPotongans[] = [
                    'potongan_id' => $potongan->id,
                    'jumlah_potongan' => $jumlahPotongan,
                ];
            }
        }

        $jumlahTidakHadir = $this->countAbsence($karyawan->id, $startDate, $tahun);
        $potonganAbsensi = round($jumlahTidakHadir * $gajiPerHari);

        if ($potonganAbsensi > 0 && $potonganAbsensiMaster) {
            $gajiPotongans[] = [
                'potongan_id' => $potonganAbsensiMaster->id,
                'jumlah_potongan' => $potonganAbsensi,
            ];
        }

        $totalPotongan = $totalPotonganTetap + $potonganAbsensi;
        return [$totalPotongan, $gajiPotongans];
    }

    /**
     * Count the number of absences for a given employee in a specific month and year.
     *
     * @param int $karyawanId
     * @param Carbon $startDate
     * @param int $tahun
     * @return int
     */
    private function countAbsence(int $karyawanId, Carbon $startDate, int $tahun): int
    {
        return Absensi::where('karyawan_id', $karyawanId)
            ->whereMonth('tanggal', $startDate->month)
            ->whereYear('tanggal', $tahun)
            ->whereIn('status_kehadiran', ['Alpha', 'Izin', 'Sakit'])
            ->count();
    }


    public function index()
    {
        // 1. Retrieve all salary records, ordered by year and month
        // We need the raw encrypted values to decrypt them later.
        $gajisRaw = Gaji::orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();

        $processedGajis = collect(); // Initialize an empty collection to hold our aggregated data

        if ($gajisRaw->isEmpty()) {
            Log::info("No raw salary data found to process.");
            // No action needed, $processedGajis remains empty
        } else {
            // 2. Group the raw data by month and year.
            // Then, for each group, decrypt and sum the 'gaji_bersih'.
            $processedGajis = $gajisRaw->groupBy(function ($item) {
                // Ensure bulan is always two digits for consistent grouping keys (e.g., '01-2023', '12-2024')
                return str_pad($item->bulan, 2, '0', STR_PAD_LEFT) . '-' . $item->tahun;
            })->map(function ($group) {
                $totalGajiBersih = 0;
                // For the status, you can take the status of the first item in the group
                // or define a more complex logic (e.g., 'Paid' if all are paid).
                // Using first() is simple and common if status is expected to be consistent within a group.
                $status = $group->first()->status;

                foreach ($group as $gaji) {
                    try {
                        // Decrypt the gaji_bersih for each individual record
                        // Cast to (int) to ensure it's treated as a number
                        $totalGajiBersih += (int) Crypt::decryptString($gaji->gaji_bersih);
                    } catch (DecryptException $e) {
                        // Log a warning if decryption fails for a specific record.
                        // This is crucial for debugging issues with APP_KEY or corrupted data.
                        Log::warning("Decryption failed for Gaji ID {$gaji->id}, bulan {$gaji->bulan}, tahun {$gaji->tahun}: " . $e->getMessage());
                        // You might choose to skip this record's value or handle it differently
                        // (e.g., set totalGajiBersih to null or a special error value)
                    } catch (\Exception $e) {
                        // Catch any other unexpected exceptions during iteration
                        Log::error("An unexpected error occurred while processing Gaji ID {$gaji->id}: " . $e->getMessage());
                    }
                }

                return [
                    'bulan' => $group->first()->bulan,
                    'tahun' => $group->first()->tahun,
                    'total_gaji' => $totalGajiBersih,
                    'status' => $status,
                ];
            })->values(); // Use ->values() to reset the array keys from the group keys to a simple numeric index.
        }

        // For debugging: Check the final processed data
        // dd($processedGajis); // Use $processedGajis, not $gaji

        // Pass the processed data to your view
        return view('gaji.index', ['gajis' => $processedGajis]);
    }

    public function index00(Request $request)
    {

        $gajis = Gaji::selectRaw('bulan, tahun, SUM(gaji_bersih) as total_gaji, MAX(status) as status')
            ->groupBy('bulan', 'tahun')
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();

        dd($gaji);
        return view('gaji.index', compact('gajis'));
    }


    public function create()
    {
        // Logika untuk menampilkan form tambah gaji
        return view('gaji.create');
    }

    
    public function show($bulan, $tahun)
    {
        $gajis = Gaji::with(['slipGaji', 'karyawan.absensis', 'gajiPotongans.potongan', 'gajiBonuses.bonus'])->where('bulan', $bulan)->where('tahun', $tahun)->get();
        return view('gaji.show', compact('gajis', 'bulan', 'tahun'));
    }

    public function decryptGaji(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $adminKey = $request->input('admin_key');

        $gajis = Gaji::with(['slipGaji', 'karyawan.absensis', 'gajiPotongans.potongan', 'gajiBonuses.bonus'])->where('bulan', $bulan)->where('tahun', $tahun)->get();

        $decryptionError = false;
        $karyawans = collect();
        if ($adminKey) {
            $gajis = $this->getDecryptedGaji($adminKey, $bulan, $tahun);
            if ($gajis === false) {
                $decryptionError = true;
                $gajis = Gaji::with(['slipGaji', 'karyawan.absensis', 'gajiPotongans.potongan', 'gajiBonuses.bonus'])->where('bulan', $bulan)->where('tahun', $tahun)->get();
                session()->flash('error', 'Kunci dekripsi tidak valid atau data rusak. Data ditampilkan dalam bentuk terenkripsi.');
                return redirect()->back();
            }
        } else {
            $gajis = Gaji::with(['slipGaji', 'karyawan.absensis', 'gajiPotongans.potongan', 'gajiBonuses.bonus'])->where('bulan', $bulan)->where('tahun', $tahun)->get();
        }
        return view('gaji.decrypt', compact('gajis'));
    }

    private function getDecryptedGaji(string $adminKey, $bulan, $tahun)
    {
        try {
            $decodedAdminKey = base64_decode($adminKey, true);
            if ($decodedAdminKey === false || strlen($decodedAdminKey) !== 32) {
                throw new \InvalidArgumentException('Kunci admin tidak valid atau panjang tidak sesuai.');
            }

            $adminEncrypter = new Encrypter($decodedAdminKey, config('app.cipher'));

            $gajis = Gaji::with(['slipGaji', 'karyawan.absensis', 'gajiPotongans.potongan', 'gajiBonuses.bonus'])->where('bulan', $bulan)->where('tahun', $tahun)->get();

            $gajis->map(function ($gaji) use ($adminEncrypter) {
                // Dekripsi nama
                try {
                    $gaji->gaji_pokok = $adminEncrypter->decryptString($gaji->gaji_pokok);
                } catch (DecryptException $e) {
                    $gaji->gaji_pokok = '[Dekripsi Gagal]';
                    Log::warning("Dekripsi Gaji Pokok  {$gaji->id} gagal: " . $e->getMessage());
                }

                // Dekripsi nik
                try {
                    $gaji->tunjangan = $adminEncrypter->decryptString($gaji->tunjangan);
                } catch (DecryptException $e) {
                    $gaji->tunjangan = '[Dekripsi Gagal]';
                    Log::warning("Dekripsi tunjangan gaji ID {$gaji->id} gagal: " . $e->getMessage());
                }

                try {
                    $gaji->total_potongan = $adminEncrypter->decryptString($gaji->total_potongan);
                } catch (DecryptException $e) {
                    $gaji->total_potongan = '[Dekripsi Gagal]';
                    Log::warning("Dekripsi total potongan gaji ID {$gaji->id} gagal: " . $e->getMessage());
                }
                try {
                    $gaji->total_bonus = $adminEncrypter->decryptString($gaji->total_bonus);
                } catch (DecryptException $e) {
                    $gaji->total_bonus = '[Dekripsi Gagal]';
                    Log::warning("Dekripsi total potongan gaji ID {$gaji->id} gagal: " . $e->getMessage());
                }
                try {
                    $gaji->karyawan->email = $adminEncrypter->decryptString($gaji->karyawan->email);
                } catch (DecryptException $e) {
                    $gaji->karyawan->email = '[Dekripsi Gagal]';
                    Log::warning("Dekripsi Email Karyawan gaji ID {$gaji->id} gagal: " . $e->getMessage());
                }

                try {
                    $gaji->gaji_bersih = $adminEncrypter->decryptString($gaji->gaji_bersih);
                } catch (DecryptException $e) {
                    $gaji->gaji_bersih = '[Dekripsi Gagal]';
                    Log::warning("Dekripsi gaji_bersih gaji ID {$gaji->id} gagal: " . $e->getMessage());
                }
                return $gaji;
            });

            return $gajis;
        } catch (DecryptException $e) {
            Log::error('Kesalahan dekripsi massal dengan kunci admin: ' . $e->getMessage());
            return false;
        } catch (\InvalidArgumentException $e) {
            Log::error('Format kunci admin tidak valid saat dekripsi massal: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Kesalahan tak terduga saat dekripsi massal: ' . $e->getMessage());
            return false;
        }
    }



    public function edit($bulan, $tahun)
    {
        $gaji = Gaji::where('bulan', $bulan)->where('tahun', $tahun)->firstOrFail();
        return view('gaji.edit', compact('gaji'));
    }

    public function destroy($bulan, $tahun)
    {
        $gaji = Gaji::where('bulan', $bulan)->where('tahun', $tahun)->delete();
        return redirect()->route('gaji.index')->with('success', 'Gaji berhasil dihapus.');
    }

   
    public function sendEmail(Request $request)
    {

        $email = $request->input('email'); // Ambil email dari input jika diperlukan
        $nama = $request->input('nama'); // Ambil email dari input jika diperlukan
        $nomor_slip = $request->input('nomor_slip'); // Ambil slip gaji dari input jika diperlukan


        $link = route('gaji.showKaryawan', ['nomor_slip' => $nomor_slip]);

        $emailSubject = 'Slip Gaji Anda Tersedia';
        $emailContent = "Halo " . $nama . ",\n\n";
        $emailContent .= "Slip gaji Anda telah tersedia.\n";
        $emailContent .= "Silakan kunjungi link berikut untuk melihat detailnya:\n\n";
        $emailContent .= $link . "\n\n";
        $emailContent .= "Terima kasih.\n\n";
        $emailContent .= "Hormat kami,\nTim Perusahaan";

        Mail::raw($emailContent, function ($message) use ($email, $emailSubject) {
            $message->to($email)
                ->subject($emailSubject);
        });


        // Logika untuk mengirim email
        // Misalnya, menggunakan Mail facade untuk mengirim email
        // Mail::send('gaji.slip_gaji', ['link' => $link], function ($message) use ($email) {
        //     $message->to($email)
        //         ->subject('Slip Gaji Bulan Ini');
        // });

        // return response()->json(['success' => true, 'message' => 'Email berhasil dikirim.']);
        return redirect()->route('gaji.index')->with('success', 'Email berhasil dikirim ke ' . $email);
    }

    public function showSlipKaryawan($nomor_slip)
    {
        // Eager load semua relasi yang diperlukan
        $slipGaji = SlipGaji::with([
            'gaji.karyawan.jabatan', // Akses jabatan dari karyawan
            'gaji.gajiPotongans.potongan',
            'gaji.gajiBonuses.bonus',
            'gaji.karyawan.absensis' // Jika data absensi akan ditampilkan
        ])
            ->where('nomor_slip', $nomor_slip)
            ->firstOrFail();

        $karyawanDecryptionError = false;
        $gajiDecryptionError = false;

        // Dekripsi data gaji (numerik)
        try {
            $slipGaji->gaji->gaji_pokok = Crypt::decryptString($slipGaji->gaji->gaji_pokok);
            $slipGaji->gaji->tunjangan = Crypt::decryptString($slipGaji->gaji->tunjangan);
            $slipGaji->gaji->total_potongan = Crypt::decryptString($slipGaji->gaji->total_potongan);
            $slipGaji->gaji->total_bonus = Crypt::decryptString($slipGaji->gaji->total_bonus);
            $slipGaji->gaji->gaji_bersih = Crypt::decryptString($slipGaji->gaji->gaji_bersih);
        } catch (DecryptException $e) {
            $gajiDecryptionError = true;
            Log::error("Dekripsi data nominal gaji untuk slip nomor {$nomor_slip} gagal: " . $e->getMessage());
            // Set nilai fallback jika dekripsi gagal
            $slipGaji->gaji->gaji_pokok = '[Gagal]';
            $slipGaji->gaji->tunjangan = '[Gagal]';
            $slipGaji->gaji->total_potongan = '[Gagal]';
            $slipGaji->gaji->total_bonus = '[Gagal]';
            $slipGaji->gaji->gaji_bersih = '[Gagal]';
        }

        // Dekripsi data karyawan (nama, NIK, email, No. HP, alamat)
        // Perhatikan bahwa Anda menggunakan $slipGaji->gaji->karyawan->nama_karyawan di dd($data),
        // namun nama kolom karyawan biasanya 'nama' atau 'name'.
        // Saya asumsikan nama kolom adalah 'nama' dan akan didekripsi ke 'nama_plain'.
        if ($slipGaji->gaji->karyawan) {
            try {
                $slipGaji->gaji->karyawan->nik_plain = Crypt::decryptString($slipGaji->gaji->karyawan->nik);
                $slipGaji->gaji->karyawan->email_plain = $slipGaji->gaji->karyawan->email ? Crypt::decryptString($slipGaji->gaji->karyawan->email) : null;
                $slipGaji->gaji->karyawan->no_hp_plain = $slipGaji->gaji->karyawan->no_hp ? Crypt::decryptString($slipGaji->gaji->karyawan->no_hp) : null;
                $slipGaji->gaji->karyawan->alamat_plain = $slipGaji->gaji->karyawan->alamat ? Crypt::decryptString($slipGaji->gaji->karyawan->alamat) : null;
            } catch (DecryptException $e) {
                $karyawanDecryptionError = true;
                Log::error("Dekripsi data karyawan untuk slip nomor {$nomor_slip} (ID Karyawan: {$slipGaji->gaji->karyawan->id}) gagal: " . $e->getMessage());
                // Set fallback jika dekripsi gagal
                $slipGaji->gaji->karyawan->nik_plain = '[Gagal]';
                $slipGaji->gaji->karyawan->email_plain = '[Gagal]';
                $slipGaji->gaji->karyawan->no_hp_plain = '[Gagal]';
                $slipGaji->gaji->karyawan->alamat_plain = '[Gagal]';
            }
        }

        // Hapus dd($data);
        // return view('gaji.show_karyawan', compact('slipGaji', 'karyawanDecryptionError', 'gajiDecryptionError'));
        return view('gaji.show_karyawan', compact('slipGaji')); // Jika Anda hanya
    }

    /**
     * Metode baru untuk menghasilkan dan mengunduh PDF slip gaji.
     */
    public function downloadSlipGajiPdf($nomor_slip)
    {
        $slipGaji = SlipGaji::with([
            'gaji.karyawan.jabatan',
            'gaji.gajiPotongans.potongan',
            'gaji.gajiBonuses.bonus'
        ])
            ->where('nomor_slip', $nomor_slip)
            ->firstOrFail();

        // ---- DEKRIPSI DATA SEBELUM DIKIRIM KE VIEW PDF ----
        $gajiDecryptionError = false;
        try {
            $slipGaji->gaji->gaji_pokok = (float) Crypt::decryptString($slipGaji->gaji->gaji_pokok);
            $slipGaji->gaji->tunjangan = (float) Crypt::decryptString($slipGaji->gaji->tunjangan);
            $slipGaji->gaji->total_potongan = (float) Crypt::decryptString($slipGaji->gaji->total_potongan);
            $slipGaji->gaji->total_bonus = (float) Crypt::decryptString($slipGaji->gaji->total_bonus);
            $slipGaji->gaji->gaji_bersih = (float) Crypt::decryptString($slipGaji->gaji->gaji_bersih);
        } catch (DecryptException $e) {
            $gajiDecryptionError = true;
            Log::error("Dekripsi nominal gaji untuk PDF slip nomor {$nomor_slip} gagal: " . $e->getMessage());
            // Set nilai fallback untuk PDF
            $slipGaji->gaji->gaji_pokok = 0;
            $slipGaji->gaji->tunjangan = 0;
            $slipGaji->gaji->total_potongan = 0;
            $slipGaji->gaji->total_bonus = 0;
            $slipGaji->gaji->gaji_bersih = 0;
        }

        $karyawanDecryptionError = false;
        if ($slipGaji->gaji->karyawan) {
            try {
                $slipGaji->gaji->karyawan->nik = Crypt::decryptString($slipGaji->gaji->karyawan->nik);
                $slipGaji->gaji->karyawan->email = $slipGaji->gaji->karyawan->email ? Crypt::decryptString($slipGaji->gaji->karyawan->email) : null;
                $slipGaji->gaji->karyawan->no_hp = $slipGaji->gaji->karyawan->no_hp ? Crypt::decryptString($slipGaji->gaji->karyawan->no_hp) : null;
                $slipGaji->gaji->karyawan->alamat = $slipGaji->gaji->karyawan->alamat ? Crypt::decryptString($slipGaji->gaji->karyawan->alamat) : null;
            } catch (DecryptException $e) {
                $karyawanDecryptionError = true;
                Log::error("Dekripsi data karyawan untuk PDF slip nomor {$nomor_slip} gagal: " . $e->getMessage());
                // Set nilai fallback untuk PDF
                $slipGaji->gaji->karyawan->nik = '[Gagal]';
                $slipGaji->gaji->karyawan->email = '[Gagal]';
                $slipGaji->gaji->karyawan->no_hp = '[Gagal]';
                $slipGaji->gaji->karyawan->alamat = '[Gagal]';
            }
        }
        // ----------------------------------------------------

        $pdf = Pdf::loadView('gaji.slip_gaji_download', compact('slipGaji', 'gajiDecryptionError', 'karyawanDecryptionError'));

        $filename = 'Slip_Gaji_' . ($slipGaji->gaji->karyawan->nama_plain ?? 'Karyawan') . '_' .
            \Carbon\Carbon::createFromDate($slipGaji->gaji->tahun, $slipGaji->gaji->bulan, 1)->format('M_Y') . '.pdf';

        return $pdf->stream($filename); // Atau $pdf->stream($filename); untuk melihat di browser
    }
    
    public function laporanView()
    {
        return view('gaji.laporan');
    }
    
     public function laporanCetak00(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $adminKey = $request->input('admin_key');

        $gajis = Gaji::with(['slipGaji', 'karyawan.absensis', 'gajiPotongans.potongan', 'gajiBonuses.bonus'])->where('bulan', $bulan)->where('tahun', $tahun)->get();

        $decryptionError = false;
        $karyawans = collect();
        if ($adminKey) {
            $gajis = $this->getDecryptedGaji($adminKey, $bulan, $tahun);
            if ($gajis === false) {
                $decryptionError = true;
                $gajis = Gaji::with(['slipGaji', 'karyawan.absensis', 'gajiPotongans.potongan', 'gajiBonuses.bonus'])->where('bulan', $bulan)->where('tahun', $tahun)->get();
                session()->flash('error', 'Kunci dekripsi tidak valid atau data rusak. Data ditampilkan dalam bentuk terenkripsi.');
                return redirect()->back();
            }
        } else {
            $gajis = Gaji::with(['slipGaji', 'karyawan.absensis', 'gajiPotongans.potongan', 'gajiBonuses.bonus'])->where('bulan', $bulan)->where('tahun', $tahun)->get();
        }
            $pdf = PDF::loadView('absensi.print-rekap-bulanan', [
            'rekapDataFinal' => $rekapDataFinal,
            'namaBulan' => $namaBulan,
            'tahun' => $tahun
        ])->setPaper('A4', 'portrait');
    
        // Opsi stream untuk preview atau download untuk langsung mengunduh
        return $pdf->stream('rekap-absensi-bulanan-' . $bulan . '-' . $tahun . '.pdf');
        return view('gaji.decrypt', compact('gajis'));
    }
     /**
     * Mencetak laporan PDF gaji dengan dekripsi otomatis.
     */
     public function laporanCetak(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

      
        $gajis = Gaji::with(['karyawan.jabatan', 'gajiBonuses', 'gajiPotongans'])
                     ->where('bulan', $bulan)
                     ->where('tahun', $tahun)
                     ->get();

        if ($gajis->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data gaji untuk periode yang dipilih.');
        }

    
        $decryptedGajis = $this->decryptGajiCollection($gajis);
        
   
        $grandTotalGajiBersih = $decryptedGajis->sum(function($gaji) {
            return is_numeric($gaji->gaji_bersih) ? (float) $gaji->gaji_bersih : 0;
        });

       
        $daftarBulan = [ 1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember' ];
        $namaBulan = $daftarBulan[(int)$bulan] ?? 'Bulan Tidak Valid';

        
        $dataForPdf = [
            'gajis' => $decryptedGajis,
            'namaBulan' => $namaBulan,
            'tahun' => $tahun,
            'grandTotalGajiBersih' => $grandTotalGajiBersih,
        ];

        // 6. Generate PDF. 
        $pdf = PDF::loadView('gaji.print-gaji-karyawan', $dataForPdf)->setPaper('A4', 'landscape');
        return $pdf->stream('laporan-gaji-' . $bulan . '-' . $tahun . '.pdf');
    }

    /**
     * Method helper untuk dekripsi.
     * 
     */
    private function decryptGajiCollection(Collection $gajis): Collection
    {
        $decryptedItems = [];

        foreach ($gajis as $gaji) {
            
            try { $gaji->gaji_pokok = Crypt::decryptString($gaji->gaji_pokok); } catch (DecryptException $e) { $gaji->gaji_pokok = '[Gagal Dekripsi]'; }
            try { $gaji->tunjangan = Crypt::decryptString($gaji->tunjangan); } catch (DecryptException $e) { $gaji->tunjangan = '[Gagal Dekripsi]'; }
            try { $gaji->total_bonus = Crypt::decryptString($gaji->total_bonus); } catch (DecryptException $e) { $gaji->total_bonus = '[Gagal Dekripsi]'; }
            try { $gaji->total_potongan = Crypt::decryptString($gaji->total_potongan); } catch (DecryptException $e) { $gaji->total_potongan = '[Gagal Dekripsi]'; }
            try { $gaji->gaji_bersih = Crypt::decryptString($gaji->gaji_bersih); } catch (DecryptException $e) { $gaji->gaji_bersih = '[Gagal Dekripsi]'; }
            if ($gaji->karyawan && $gaji->karyawan->nik) {
                try { $gaji->karyawan->nik = Crypt::decryptString($gaji->karyawan->nik); } catch (DecryptException $e) { $gaji->karyawan->nik = '[Gagal Dekripsi]'; }
            }
            if ($gaji->karyawan && $gaji->karyawan->email) {
                try { $gaji->karyawan->email = Crypt::decryptString($gaji->karyawan->email); } catch (DecryptException $e) { $gaji->karyawan->email = '[Gagal Dekripsi]'; }
            }
            
            $decryptedItems[] = $gaji;
        }

      return new \Illuminate\Database\Eloquent\Collection($decryptedItems);
    }
    
    public function laporanSlip()
    {
        $karyawans = Karyawan::select('id','nama')->orderBy('nama')->get();
        return view('gaji.laporan-slipgaji',compact('karyawans'));
    }
    
    
    
     /**
     * Membuat dan men-stream PDF slip gaji untuk karyawan dan periode tertentu.
     * Nama method disesuaikan dengan route 'laporan.gaji.cetak'
     */
    public function cetakSlipGaji(Request $request)
    {
        // 1. Ambil input dari form
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $karyawanId = $request->input('karyawan_id');

        // 2. Validasi input dasar
        if (!$bulan || !$tahun) {
            return redirect()->back()->with('error', 'Bulan dan Tahun wajib diisi.');
        }

        // Jika user memilih karyawan spesifik, cetak slip gaji
        if ($karyawanId) {
            return $this->cetakSlipGajiSpesifik($karyawanId, $bulan, $tahun);
        } else {
            // Jika tidak ada karyawan yang dipilih, panggil fungsi untuk cetak laporan rekapitulasi
            // (Ini memanggil fungsi yang sudah kita buat sebelumnya)
         
            // return $this->laporanCetak($request);
            
             return redirect()->back()->with('error', 'Karyawan wajib di isi!');
        }
    }

    private function cetakSlipGajiSpesifik($karyawanId, $bulan, $tahun)
    {
        // 1. Ambil data Gaji asli dari database, lengkap dengan relasinya
        $gajiAsli = Gaji::where('karyawan_id', $karyawanId)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->with(['karyawan.jabatan', 'gajiBonuses.bonus', 'gajiPotongans.potongan', 'slipGaji'])
                        ->first();
    
        // 2. Jika data gaji tidak ditemukan
        if (!$gajiAsli) {
            return redirect()->back()->with('error', 'Data gaji untuk karyawan dan periode yang dipilih tidak ditemukan.');
        }
    
        // 3. Buat array baru yang bersih untuk menghindari konflik dengan Accessor Model
        $dataBersih = [];
    
        try {
            // 4. Isi array dengan semua data yang sudah didekripsi
            $dataBersih['gaji_pokok']     = Crypt::decryptString($gajiAsli->gaji_pokok);
            $dataBersih['tunjangan']      = Crypt::decryptString($gajiAsli->tunjangan);
            $dataBersih['total_bonus']    = Crypt::decryptString($gajiAsli->total_bonus);
            $dataBersih['total_potongan'] = Crypt::decryptString($gajiAsli->total_potongan);
            $dataBersih['gaji_bersih']    = Crypt::decryptString($gajiAsli->gaji_bersih);
    
            // 5. Salin juga relasi yang dibutuhkan oleh view ke dalam array
            $dataBersih['karyawan'] = $gajiAsli->karyawan;
            // Dekripsi field NIK di dalam data karyawan yang sudah disalin
            if ($dataBersih['karyawan']) {
                $dataBersih['karyawan']->nik = Crypt::decryptString($dataBersih['karyawan']->nik);
                // Tambahkan dekripsi untuk field karyawan lain jika ada di view
                // $dataBersih['karyawan']->email = Crypt::decryptString($dataBersih['karyawan']->email);
            }
            
            $dataBersih['gajiBonuses'] = $gajiAsli->gajiBonuses;
            $dataBersih['gajiPotongans'] = $gajiAsli->gajiPotongans;
            $dataBersih['slipGaji'] = $gajiAsli->slipGaji;
    
        } catch (DecryptException $e) {
            Log::error("Gagal dekripsi saat membuat slip gaji untuk Gaji ID: {$gajiAsli->id}. Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mendekripsi data. Data sumber mungkin rusak.');
        }
    
        // 6. Siapkan nama bulan
        $daftarBulan = [ 1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember' ];
        $namaBulan = $daftarBulan[(int)$bulan] ?? 'Tidak Valid';
        
        // 7. Siapkan data final untuk PDF
        $dataForPdf = [
            // --- INI BAGIAN KUNCINYA ---
            // Kirim data bersih ini dengan nama variabel 'gaji'
            // (object) mengubah array menjadi object, agar di view tetap bisa pakai '->'
            'gaji' => (object) $dataBersih, 
            'namaBulan' => $namaBulan,
            'tahun' => $tahun,
        ];
        // 8. Generate PDF
        $pdf = PDF::loadView('gaji.print-laporan-slipgaji', $dataForPdf)->setPaper('A4', 'portrait');
        return $pdf->stream('slip-gaji-'.$bulan.'-'.$tahun.'.pdf');
    }

}
