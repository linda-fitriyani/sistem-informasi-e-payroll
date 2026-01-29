@extends('layouts.layoutmaster')
@section('title', 'Laporan Gaji Karyawan')
@section('css')
    <style>
     
    </style>
@endsection

@section('content')
 <div class="row">
    <section class="section">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Laporan Gaji Karyawan</h5>
          <!-- Floating Labels Form -->
            <form action="{{ route('laporan.gaji.cetak') }}" method="post">
              @csrf
              <div class="card p-4 flex justify-content-between">
                  <div class="row">
                      <div class="col-md-3">
                          <label for="bulan">Bulan</label>
                          <select name="bulan" class="form-control">
                            @foreach([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember'
                            ] as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>

                      </div>
                      <div class="col-md-3">
                          <label for="tahun">Tahun</label>
                          <input type="number" name="tahun" class="form-control" value="{{ date('Y') }}">
                      </div>
                      <div class="col-md-3">
                          <label for="">Submit :</label>
                          <div class=" d-flex gap-2">
                              <button type="submit" name="export" value="pdf" class="btn button-tambah ">
                                  <i class="bi bi-printer"></i> Cetak PDF
                              </button>
                              <!-- <button type="submit" name="export" value="excel" class="btn btn-success">
                                  <i class="bi bi-file-earmark-excel"></i> Excel
                              </button> -->
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
