@extends('layouts.layoutmaster') 
@section('title', 'Ganti Password')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
               
                <div class="card-body p-4">

                    {{-- Menampilkan Pesan Sukses atau Error --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.change.update') }}">
                        @csrf

                        {{-- ======================================================= --}}
                        {{-- DIUBAH: Menggunakan Input Group dengan Ikon --}}
                        {{-- ======================================================= --}}
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                <input id="current_password" type="password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       name="current_password" required autocomplete="current-password"
                                       placeholder="Masukkan password Anda yang sekarang">
                                @error('current_password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- ======================================================= --}}
                        {{-- DIUBAH: Menggunakan Input Group dengan Ikon --}}
                        {{-- ======================================================= --}}
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input id="new_password" type="password"
                                       class="form-control @error('new_password') is-invalid @enderror"
                                       name="new_password" required autocomplete="new-password"
                                       placeholder="Minimal 8 karakter">
                                @error('new_password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        {{-- ======================================================= --}}
                        {{-- DIUBAH: Menggunakan Input Group dengan Ikon --}}
                        {{-- ======================================================= --}}
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                <input id="new_password_confirmation" type="password"
                                       class="form-control"
                                       name="new_password_confirmation" required autocomplete="new-password"
                                       placeholder="Ketik ulang password baru Anda">
                            </div>
                        </div>

                        {{-- Tombol Submit --}}
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn button-tambah">
                                <i class="bi bi-save me-2"></i> Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection