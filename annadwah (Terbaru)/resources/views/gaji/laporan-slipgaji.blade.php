@extends('layouts.layoutmaster')
@section('title', 'Laporan Slip Gaji Karyawan')
@section('css')
    <style>
        /* Gaya CSS Anda bisa ditambahkan di sini */
    </style>
@endsection

@section('content')
 <div class="row">
    <section class="section">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Laporan Slip Gaji Karyawan</h5>
          
          <form action="{{ route('laporan.slipgaji.cetak') }}" method="post">
              @csrf
              <div class="card p-4 flex justify-content-between">
                  <div class="row">
                      {{-- =============================================== --}}
                      {{-- TAMBAHAN: Dropdown Karyawan --}}
                      {{-- =============================================== --}}
                      <div class="col-md-3">
                          <label for="karyawan_id" class="form-label">Pilih Karyawan</label>
                          <select name="karyawan_id" id="karyawan_id" class="form-select">
                              {{-- Opsi untuk memilih semua karyawan --}}
                              @if(isset($karyawans))
                                  @foreach ($karyawans as $karyawan)
                                      <option value="{{ $karyawan->id }}">{{ $karyawan->nama }}</option>
                                  @endforeach
                              @endif
                          </select>
                      </div>
                      {{-- Dropdown Bulan --}}
                      <div class="col-md-3">
                          <label for="bulan" class="form-label">Bulan</label>
                          <select name="bulan" id="bulan" class="form-select">
                              @foreach([
                                  1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                  5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                  9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                              ] as $num => $name)
                                  {{-- Pilih bulan saat ini sebagai default --}}
                                  <option value="{{ $num }}" {{ date('n') == $num ? 'selected' : '' }}>
                                      {{ $name }}
                                  </option>
                              @endforeach
                          </select>
                      </div>
                      
                      {{-- Input Tahun --}}
                      <div class="col-md-3">
                          <label for="tahun" class="form-label">Tahun</label>
                          <input type="number" name="tahun" id="tahun" class="form-control" value="{{ date('Y') }}">
                      </div>

                      

                      {{-- Tombol Submit --}}
                      <div class="col-md-3">
                          <label for="" class="form-label">Aksi</label>
                          <div class="d-flex gap-2">
                              <button type="submit" name="export" value="pdf" class="btn button-tambah">
                                  <i class="bi bi-printer"></i> Cetak PDF
                              </button>
                              </div>
                      </div>

                  </div>
              </div>
          </form>

        </div>
      </div>
    </section>
  </div>
@endsection