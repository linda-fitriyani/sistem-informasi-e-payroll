<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangePasswordControllerUser extends Controller
{
    /**
     * Menampilkan form untuk ganti password.
     */
    public function showChangePasswordForm()
    {
        return view('auth.passwords.change'); // Kita akan membuat view ini di Langkah 3
    }

    /**
     * Memproses perubahan password.
     */
    public function changePassword(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'current_password'      => 'required',
            'new_password'          => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required'     => 'Password baru wajib diisi.',
            'new_password.min'          => 'Password baru minimal harus 8 karakter.',
            'new_password.confirmed'    => 'Konfirmasi password baru tidak cocok.',
        ]);

        // 2. Cek apakah password saat ini cocok
        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            // Jika tidak cocok, kembalikan dengan pesan error
            return back()->with('error', 'Password saat ini yang Anda masukkan salah!');
        }

        // 3. Jika cocok, update password baru
        $user->password = Hash::make($request->new_password);
        $user->save();

        // 4. Redirect kembali dengan pesan sukses
        return back()->with('success', 'Password berhasil diubah!');
    }
}