@extends('layouts.app')

@section('title', 'Edit Formulir Pendaftaran: ' . $pendaftaran->nama_lengkap)

@section('header-actions')
@php
    $backUrl = request('from') === 'siswa' ? route('siswa') : route('pendaftaran.index');
    $backLabel = request('from') === 'siswa' ? 'Kembali ke Siswa' : 'Kembali ke Daftar';
@endphp
<a href="{{ $backUrl }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    {{ $backLabel }}
</a>
@endsection

@section('content')
<div class="form-container" style="width: 100%;">

    {{-- Error Banner --}}
    @if ($errors->any())
    <div class="alert-banner alert-error" style="margin-bottom: 20px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <strong>Terdapat kesalahan pada isian formulir:</strong>
            <ul style="margin: 4px 0 0 18px; font-size: 13px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Success Banner --}}
    @if (session('success'))
    <div class="alert-banner alert-success" style="margin-bottom: 20px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    <form action="{{ route('pendaftaran.update', $pendaftaran->id) }}" method="POST" enctype="multipart/form-data" id="formPendaftaran">
        @csrf
        @method('PUT')
        <input type="hidden" name="from" value="{{ request('from', 'pendaftaran') }}">

        <div class="wizard-card">
            {{-- STEPPER HEADER ALA GENERUS --}}
            <div class="wizard-stepper" id="wizardStepper">
                {{-- STEP 1 --}}
                <div class="wizard-step is-active" data-step="1" id="tab_step_1">
                    <div class="wizard-step-circle">01</div>
                    <div class="wizard-step-title">Cek Identitas</div>
                </div>

                {{-- CHEVRON 1 --}}
                <div class="wizard-divider" aria-hidden="true">
                    <svg viewBox="0 0 16 60" fill="none" preserveAspectRatio="none">
                        <path d="M0 0 L14 30 L0 60" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </div>

                {{-- STEP 2 --}}
                <div class="wizard-step" data-step="2" id="tab_step_2">
                    <div class="wizard-step-circle">02</div>
                    <div class="wizard-step-title">Data Pribadi</div>
                </div>

                {{-- CHEVRON 2 --}}
                <div class="wizard-divider" aria-hidden="true">
                    <svg viewBox="0 0 16 60" fill="none" preserveAspectRatio="none">
                        <path d="M0 0 L14 30 L0 60" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </div>

                {{-- STEP 3 --}}
                <div class="wizard-step" data-step="3" id="tab_step_3">
                    <div class="wizard-step-circle">03</div>
                    <div class="wizard-step-title">Keluarga</div>
                </div>

                {{-- CHEVRON 3 --}}
                <div class="wizard-divider" aria-hidden="true">
                    <svg viewBox="0 0 16 60" fill="none" preserveAspectRatio="none">
                        <path d="M0 0 L14 30 L0 60" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </div>

                {{-- STEP 4 --}}
                <div class="wizard-step" data-step="4" id="tab_step_4">
                    <div class="wizard-step-circle">04</div>
                    <div class="wizard-step-title">Alamat</div>
                </div>
            </div>

            {{-- WIZARD BODY --}}
            <div class="wizard-body">

                {{-- ========================================================
                     LANGKAH 01: CEK IDENTITAS & PILIHAN PENDAFTARAN
                     ======================================================== --}}
                <div class="wizard-step-pane is-active" id="pane_step_1">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                        
                        {{-- Jenis Identitas --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="jenis_identitas">
                                Jenis Identitas <span class="required-mark">*</span>
                            </label>
                            <select id="jenis_identitas" class="wizard-select">
                                <option value="NIK" selected>NIK</option>
                            </select>
                        </div>

                        {{-- Nomor Identitas (NIK) --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nik">
                                Nomor Identitas <span class="required-mark">*</span>
                            </label>
                            <input type="text" name="nik" id="nik" maxlength="16" value="{{ old('nik', $pendaftaran->nik) }}"
                                   class="wizard-input @error('nik') is-invalid @enderror"
                                   placeholder="Nomor Identitas" required>
                            @error('nik')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Daftar Sebagai (Pilihan Jenjang di Awal) --}}
                        <div class="wizard-form-group" style="grid-column: 1 / -1; max-width: 480px; width: 100%;">
                            <label class="wizard-label" for="jenjang">
                                Daftar Sebagai <span class="required-mark">*</span>
                            </label>
                            <select name="jenjang" id="jenjang" class="wizard-select @error('jenjang') is-invalid @enderror" required>
                                <option value="">Pilih salah satu opsi</option>
                                <option value="SMP" {{ old('jenjang', $pendaftaran->jenjang) == 'SMP' ? 'selected' : '' }}>SMP</option>
                                <option value="SMA" {{ old('jenjang', $pendaftaran->jenjang) == 'SMA' ? 'selected' : '' }}>SMA</option>
                                <option value="Reguler" {{ old('jenjang', $pendaftaran->jenjang) == 'Reguler' ? 'selected' : '' }}>Reguler</option>
                            </select>
                            @error('jenjang')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- NISN (Nomor Induk Siswa Nasional) --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nisn">
                                NISN (Nomor Induk Siswa Nasional) <span class="required-mark" id="mark_nisn" style="display:none;">*</span>
                            </label>
                            <input type="text" name="nisn" id="nisn" maxlength="20" value="{{ old('nisn', $pendaftaran->nisn) }}"
                                   class="wizard-input @error('nisn') is-invalid @enderror"
                                   placeholder="Nomor Induk Siswa Nasional">
                            @error('nisn')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- No. Induk Santri (Opsional) --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="no_induk">
                                No. Induk Santri (Opsional)
                            </label>
                            <input type="text" name="no_induk" id="no_induk" value="{{ old('no_induk', $pendaftaran->no_induk) }}"
                                   class="wizard-input @error('no_induk') is-invalid @enderror"
                                   placeholder="No. Induk Santri">
                            @error('no_induk')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    {{-- Actions Step 1 --}}
                    <div class="wizard-footer" style="justify-content: flex-end;">
                        <button type="button" class="btn-wizard-next js-btn-next" data-next="2">
                            Selanjutnya
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ========================================================
                     LANGKAH 02: DATA PRIBADI & RIWAYAT PENDIDIKAN
                     ======================================================== --}}
                <div class="wizard-step-pane" id="pane_step_2">
                    
                    {{-- 1. IDENTITAS PRIBADI --}}
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 24px;">
                        {{-- Nama Lengkap --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nama_lengkap">
                                Nama Lengkap <span class="required-mark">*</span>
                            </label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $pendaftaran->nama_lengkap) }}"
                                   class="wizard-input @error('nama_lengkap') is-invalid @enderror" placeholder="Nama Lengkap Sesuai KK/Akta" required>
                            @error('nama_lengkap')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nama Panggilan --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nama_panggilan">
                                Nama Panggilan <span class="required-mark">*</span>
                            </label>
                            <input type="text" name="nama_panggilan" id="nama_panggilan" value="{{ old('nama_panggilan', $pendaftaran->nama_panggilan) }}"
                                   class="wizard-input @error('nama_panggilan') is-invalid @enderror" placeholder="Nama Panggilan" required>
                            @error('nama_panggilan')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Jenis Kelamin --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="jenis_kelamin">
                                Jenis Kelamin <span class="required-mark">*</span>
                            </label>
                            <select name="jenis_kelamin" id="jenis_kelamin" class="wizard-select @error('jenis_kelamin') is-invalid @enderror" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-laki" {{ old('jenis_kelamin', $pendaftaran->jenis_kelamin) == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="Perempuan" {{ old('jenis_kelamin', $pendaftaran->jenis_kelamin) == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('jenis_kelamin')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tempat Lahir --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tempat_lahir">
                                Tempat Lahir <span class="required-mark">*</span>
                            </label>
                            <input type="text" name="tempat_lahir" id="tempat_lahir" value="{{ old('tempat_lahir', $pendaftaran->tempat_lahir) }}"
                                   class="wizard-input @error('tempat_lahir') is-invalid @enderror" placeholder="Kota / Kabupaten Lahir" required>
                            @error('tempat_lahir')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tanggal Lahir --}}
                        @php
                            $tglLahirVal = $pendaftaran->tanggal_lahir ? (is_string($pendaftaran->tanggal_lahir) ? substr($pendaftaran->tanggal_lahir, 0, 10) : $pendaftaran->tanggal_lahir->format('Y-m-d')) : '';
                        @endphp
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tanggal_lahir">
                                Tanggal Lahir <span class="required-mark">*</span>
                            </label>
                            <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir', $tglLahirVal) }}"
                                   class="wizard-input @error('tanggal_lahir') is-invalid @enderror" required>
                            @error('tanggal_lahir')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Anak Ke- --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="anak_ke">
                                Anak Ke- <span class="required-mark">*</span>
                            </label>
                            <input type="number" name="anak_ke" id="anak_ke" min="1" value="{{ old('anak_ke', $pendaftaran->anak_ke) }}"
                                   class="wizard-input @error('anak_ke') is-invalid @enderror" placeholder="1" required>
                            @error('anak_ke')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Dari Jumlah Saudara --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="jumlah_saudara">
                                Dari Jumlah Saudara <span class="required-mark">*</span>
                            </label>
                            <input type="number" name="jumlah_saudara" id="jumlah_saudara" min="0" value="{{ old('jumlah_saudara', $pendaftaran->jumlah_saudara) }}"
                                   class="wizard-input @error('jumlah_saudara') is-invalid @enderror" placeholder="0" required>
                            @error('jumlah_saudara')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tinggal Bersama --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tinggal_bersama">
                                Tinggal Bersama <span class="required-mark">*</span>
                            </label>
                            <select name="tinggal_bersama" id="tinggal_bersama" class="wizard-select @error('tinggal_bersama') is-invalid @enderror" required>
                                <option value="">Pilih Tinggal Bersama</option>
                                @foreach (['Orang Tua Kandung', 'Ayah', 'Ibu', 'Wali'] as $tb)
                                    <option value="{{ $tb }}" {{ old('tinggal_bersama', $pendaftaran->tinggal_bersama) == $tb ? 'selected' : '' }}>{{ $tb }}</option>
                                @endforeach
                            </select>
                            @error('tinggal_bersama')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Mulai Sambung Pengajian --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="mulai_pengajian">
                                Mulai Sambung Pengajian (Tahun)
                            </label>
                            <input type="text" name="mulai_pengajian" id="mulai_pengajian" value="{{ old('mulai_pengajian', $pendaftaran->mulai_pengajian) }}"
                                   class="wizard-input @error('mulai_pengajian') is-invalid @enderror" placeholder="YYYY (Contoh: 2024)">
                            @error('mulai_pengajian')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status Mondok --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="status_mondok">
                                Status Mondok <span class="required-mark">*</span>
                            </label>
                            <select name="status_mondok" id="status_mondok" class="wizard-select @error('status_mondok') is-invalid @enderror" required>
                                <option value="Person" {{ old('status_mondok', $pendaftaran->status_mondok ?? 'Person') == 'Person' ? 'selected' : '' }}>Person (Mandiri)</option>
                                <option value="Utusan/Kiriman" {{ old('status_mondok', $pendaftaran->status_mondok) == 'Utusan/Kiriman' ? 'selected' : '' }}>Utusan / Kiriman</option>
                            </select>
                            @error('status_mondok')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- 2. PENDIDIKAN & ASAL SEKOLAH --}}
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 24px; padding-top: 18px; border-top: 1px solid #F3F4F6;">
                        {{-- Pendidikan Terakhir --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="pendidikan_terakhir">
                                Pendidikan Terakhir <span class="required-mark">*</span>
                            </label>
                            <select name="pendidikan_terakhir" id="pendidikan_terakhir" class="wizard-select @error('pendidikan_terakhir') is-invalid @enderror" required>
                                <option value="">Pilih Pendidikan Terakhir</option>
                                @foreach (['SD/MI', 'SMP/MTs', 'SMA/MA/SMK', 'D3', 'S1/D4', 'Lainnya'] as $pddk)
                                    <option value="{{ $pddk }}" {{ old('pendidikan_terakhir', $pendaftaran->pendidikan_terakhir ?? 'SD/MI') == $pddk ? 'selected' : '' }}>{{ $pddk }}</option>
                                @endforeach
                            </select>
                            @error('pendidikan_terakhir')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nama Sekolah Asal --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nama_sekolah_asal" id="label_nama_sekolah_asal">
                                Nama Sekolah Asal
                            </label>
                            <input type="text" name="nama_sekolah_asal" id="nama_sekolah_asal" value="{{ old('nama_sekolah_asal', $pendaftaran->nama_sekolah_asal) }}"
                                   class="wizard-input @error('nama_sekolah_asal') is-invalid @enderror" placeholder="Nama Sekolah Asal">
                            @error('nama_sekolah_asal')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Jurusan --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="jurusan">
                                Jurusan / Program Studi
                            </label>
                            <input type="text" name="jurusan" id="jurusan" value="{{ old('jurusan', $pendaftaran->jurusan) }}"
                                   class="wizard-input @error('jurusan') is-invalid @enderror" placeholder="Kosongkan jika SD / SMP">
                            @error('jurusan')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tahun Lulus --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tahun_lulus">
                                Tahun Lulus
                            </label>
                            <input type="text" name="tahun_lulus" id="tahun_lulus" value="{{ old('tahun_lulus', $pendaftaran->tahun_lulus) }}"
                                   class="wizard-input @error('tahun_lulus') is-invalid @enderror" placeholder="YYYY (Contoh: 2026)">
                            @error('tahun_lulus')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- 3. BANTUAN SOSIAL & KIP --}}
                    @php
                        $isKip = old('penerima_kip', $pendaftaran->penerima_kip ? '1' : '0');
                    @endphp
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 24px; padding-top: 18px; border-top: 1px solid #F3F4F6;">
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="penerima_kip">
                                Penerima KIP / PIP / PKH <span class="required-mark">*</span>
                            </label>
                            <select name="penerima_kip" id="penerima_kip" class="wizard-select @error('penerima_kip') is-invalid @enderror">
                                <option value="0" {{ $isKip == '0' ? 'selected' : '' }}>Tidak</option>
                                <option value="1" {{ $isKip == '1' ? 'selected' : '' }}>Ya</option>
                            </select>
                            @error('penerima_kip')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="wizard-form-group" id="wrapper_nomor_kip" style="{{ $isKip == '1' ? 'display:flex;' : 'display:none;' }}">
                            <label class="wizard-label" for="nomor_kip">
                                Nomor KIP / KKS / PKH
                            </label>
                            <input type="text" name="nomor_kip" id="nomor_kip" value="{{ old('nomor_kip', $pendaftaran->nomor_kip) }}"
                                   class="wizard-input @error('nomor_kip') is-invalid @enderror" placeholder="Nomor Kartu KIP / KKS / PKH">
                            @error('nomor_kip')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- 4. RIWAYAT KESEHATAN & FISIK --}}
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; padding-top: 18px; border-top: 1px solid #F3F4F6;">
                        {{-- Penyakit Diderita --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="penyakit_diderita">
                                Penyakit Pernah / Sedang Diderita
                            </label>
                            <input type="text" name="penyakit_diderita" id="penyakit_diderita" value="{{ old('penyakit_diderita', $pendaftaran->penyakit_diderita) }}"
                                   class="wizard-input @error('penyakit_diderita') is-invalid @enderror" placeholder="Riwayat Penyakit (jika ada)">
                        </div>

                        {{-- Alergi --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="alergi">
                                Alergi Makanan / Obat
                            </label>
                            <input type="text" name="alergi" id="alergi" value="{{ old('alergi', $pendaftaran->alergi) }}"
                                   class="wizard-input @error('alergi') is-invalid @enderror" placeholder="Alergi (jika ada)">
                        </div>

                        {{-- Golongan Darah --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="gol_darah">
                                Golongan Darah
                            </label>
                            <select name="gol_darah" id="gol_darah" class="wizard-select @error('gol_darah') is-invalid @enderror">
                                <option value="">Pilih Golongan Darah</option>
                                @foreach (['A', 'B', 'AB', 'O', 'Tidak Tahu'] as $gd)
                                    <option value="{{ $gd }}" {{ old('gol_darah', $pendaftaran->gol_darah) == $gd ? 'selected' : '' }}>{{ $gd }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tinggi Badan --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tinggi_badan">
                                Tinggi Badan (cm)
                            </label>
                            <input type="number" name="tinggi_badan" id="tinggi_badan" value="{{ old('tinggi_badan', $pendaftaran->tinggi_badan) }}"
                                   class="wizard-input @error('tinggi_badan') is-invalid @enderror" placeholder="150">
                        </div>

                        {{-- Berat Badan --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="berat_badan">
                                Berat Badan (kg)
                            </label>
                            <input type="number" name="berat_badan" id="berat_badan" value="{{ old('berat_badan', $pendaftaran->berat_badan) }}"
                                   class="wizard-input @error('berat_badan') is-invalid @enderror" placeholder="45">
                        </div>

                        {{-- Keahlian / Prestasi --}}
                        <div class="wizard-form-group" style="grid-column: 1 / -1;">
                            <label class="wizard-label" for="keahlian_prestasi">
                                Keahlian Khusus / Prestasi
                            </label>
                            <textarea name="keahlian_prestasi" id="keahlian_prestasi" rows="2"
                                      class="wizard-textarea @error('keahlian_prestasi') is-invalid @enderror"
                                      placeholder="Keahlian, minat atau prestasi santri">{{ old('keahlian_prestasi', $pendaftaran->keahlian_prestasi) }}</textarea>
                        </div>
                    </div>

                    {{-- Actions Step 2 --}}
                    <div class="wizard-footer">
                        <button type="button" class="btn-wizard-prev js-btn-prev" data-prev="1">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            Sebelumnya
                        </button>
                        <button type="button" class="btn-wizard-next js-btn-next" data-next="3">
                            Selanjutnya
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ========================================================
                     LANGKAH 03: KELUARGA (AYAH, IBU, PENANGGUNG JAWAB)
                     ======================================================== --}}
                <div class="wizard-step-pane" id="pane_step_3">
                    
                    {{-- DATA AYAH KANDUNG --}}
                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">
                            Data Ayah Kandung
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_nama">
                                    Nama Lengkap Ayah <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="ayah_nama" id="ayah_nama" value="{{ old('ayah_nama', $pendaftaran->ayah_nama) }}"
                                       class="wizard-input @error('ayah_nama') is-invalid @enderror" placeholder="Nama Lengkap Ayah" required>
                                @error('ayah_nama')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_status">
                                    Status Ayah <span class="required-mark">*</span>
                                </label>
                                <select name="ayah_status" id="ayah_status" class="wizard-select @error('ayah_status') is-invalid @enderror" required>
                                    <option value="Hidup" {{ old('ayah_status', $pendaftaran->ayah_status ?? 'Hidup') == 'Hidup' ? 'selected' : '' }}>Hidup</option>
                                    <option value="Meninggal" {{ old('ayah_status', $pendaftaran->ayah_status) == 'Meninggal' ? 'selected' : '' }}>Meninggal</option>
                                </select>
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_ttl">Tempat, Tanggal Lahir Ayah</label>
                                <input type="text" name="ayah_ttl" id="ayah_ttl" value="{{ old('ayah_ttl', $pendaftaran->ayah_ttl) }}"
                                       class="wizard-input" placeholder="Kota, DD-MM-YYYY">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_pendidikan">Pendidikan Terakhir Ayah</label>
                                <input type="text" name="ayah_pendidikan" id="ayah_pendidikan" value="{{ old('ayah_pendidikan', $pendaftaran->ayah_pendidikan) }}"
                                       class="wizard-input" placeholder="SD / SMP / SMA / S1">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_pekerjaan">Pekerjaan Ayah</label>
                                <input type="text" name="ayah_pekerjaan" id="ayah_pekerjaan" value="{{ old('ayah_pekerjaan', $pendaftaran->ayah_pekerjaan) }}"
                                       class="wizard-input" placeholder="Wiraswasta / PNS / Buruh / dll">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_penghasilan">Penghasilan Rata-rata Ayah</label>
                                <input type="text" name="ayah_penghasilan" id="ayah_penghasilan" value="{{ old('ayah_penghasilan', $pendaftaran->ayah_penghasilan) }}"
                                       class="wizard-input" placeholder="Contoh: Rp 3.000.000 - Rp 5.000.000">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_hp">No. HP / WhatsApp Ayah</label>
                                <input type="text" name="ayah_hp" id="ayah_hp" value="{{ old('ayah_hp', $pendaftaran->ayah_hp) }}"
                                       class="wizard-input" placeholder="08xxxxxxxxxx">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_agama">Agama</label>
                                <input type="text" name="ayah_agama" id="ayah_agama" value="{{ old('ayah_agama', $pendaftaran->ayah_agama ?? 'Islam') }}"
                                       class="wizard-input" placeholder="Islam">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_wn">Kewarganegaraan</label>
                                <input type="text" name="ayah_wn" id="ayah_wn" value="{{ old('ayah_wn', $pendaftaran->ayah_wn ?? 'Indonesia') }}"
                                       class="wizard-input" placeholder="Indonesia">
                            </div>

                            <div class="wizard-form-group" style="grid-column: 1 / -1;">
                                <label class="wizard-label" for="ayah_alamat">Alamat Tinggal Ayah</label>
                                <textarea name="ayah_alamat" id="ayah_alamat" rows="2" class="wizard-textarea"
                                          placeholder="Alamat lengkap ayah">{{ old('ayah_alamat', $pendaftaran->ayah_alamat) }}</textarea>
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_daerah">Daerah Sambung</label>
                                <input type="text" name="ayah_daerah" id="ayah_daerah" value="{{ old('ayah_daerah', $pendaftaran->ayah_daerah) }}"
                                       class="wizard-input" placeholder="Daerah">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_desa">Desa Sambung</label>
                                <input type="text" name="ayah_desa" id="ayah_desa" value="{{ old('ayah_desa', $pendaftaran->ayah_desa) }}"
                                       class="wizard-input" placeholder="Desa">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_kelompok">Kelompok Sambung</label>
                                <input type="text" name="ayah_kelompok" id="ayah_kelompok" value="{{ old('ayah_kelompok', $pendaftaran->ayah_kelompok) }}"
                                       class="wizard-input" placeholder="Kelompok">
                            </div>
                        </div>
                    </div>

                    {{-- DATA IBU KANDUNG --}}
                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">
                            Data Ibu Kandung
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_nama">
                                    Nama Lengkap Ibu <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="ibu_nama" id="ibu_nama" value="{{ old('ibu_nama', $pendaftaran->ibu_nama) }}"
                                       class="wizard-input @error('ibu_nama') is-invalid @enderror" placeholder="Nama Lengkap Ibu" required>
                                @error('ibu_nama')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_status">
                                    Status Ibu <span class="required-mark">*</span>
                                </label>
                                <select name="ibu_status" id="ibu_status" class="wizard-select @error('ibu_status') is-invalid @enderror" required>
                                    <option value="Hidup" {{ old('ibu_status', $pendaftaran->ibu_status ?? 'Hidup') == 'Hidup' ? 'selected' : '' }}>Hidup</option>
                                    <option value="Meninggal" {{ old('ibu_status', $pendaftaran->ibu_status) == 'Meninggal' ? 'selected' : '' }}>Meninggal</option>
                                </select>
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_ttl">Tempat, Tanggal Lahir Ibu</label>
                                <input type="text" name="ibu_ttl" id="ibu_ttl" value="{{ old('ibu_ttl', $pendaftaran->ibu_ttl) }}"
                                       class="wizard-input" placeholder="Kota, DD-MM-YYYY">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_pendidikan">Pendidikan Terakhir Ibu</label>
                                <input type="text" name="ibu_pendidikan" id="ibu_pendidikan" value="{{ old('ibu_pendidikan', $pendaftaran->ibu_pendidikan) }}"
                                       class="wizard-input" placeholder="SD / SMP / SMA / S1">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_pekerjaan">Pekerjaan Ibu</label>
                                <input type="text" name="ibu_pekerjaan" id="ibu_pekerjaan" value="{{ old('ibu_pekerjaan', $pendaftaran->ibu_pekerjaan) }}"
                                       class="wizard-input" placeholder="Ibu Rumah Tangga / PNS / dll">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_penghasilan">Penghasilan Rata-rata Ibu</label>
                                <input type="text" name="ibu_penghasilan" id="ibu_penghasilan" value="{{ old('ibu_penghasilan', $pendaftaran->ibu_penghasilan) }}"
                                       class="wizard-input" placeholder="Contoh: Rp 2.000.000 - Rp 3.000.000">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_hp">No. HP / WhatsApp Ibu</label>
                                <input type="text" name="ibu_hp" id="ibu_hp" value="{{ old('ibu_hp', $pendaftaran->ibu_hp) }}"
                                       class="wizard-input" placeholder="08xxxxxxxxxx">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_agama">Agama</label>
                                <input type="text" name="ibu_agama" id="ibu_agama" value="{{ old('ibu_agama', $pendaftaran->ibu_agama ?? 'Islam') }}"
                                       class="wizard-input" placeholder="Islam">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_wn">Kewarganegaraan</label>
                                <input type="text" name="ibu_wn" id="ibu_wn" value="{{ old('ibu_wn', $pendaftaran->ibu_wn ?? 'Indonesia') }}"
                                       class="wizard-input" placeholder="Indonesia">
                            </div>

                            <div class="wizard-form-group" style="grid-column: 1 / -1;">
                                <label class="wizard-label" for="ibu_alamat">Alamat Tinggal Ibu</label>
                                <textarea name="ibu_alamat" id="ibu_alamat" rows="2" class="wizard-textarea"
                                          placeholder="Alamat lengkap ibu">{{ old('ibu_alamat', $pendaftaran->ibu_alamat) }}</textarea>
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_daerah">Daerah Sambung</label>
                                <input type="text" name="ibu_daerah" id="ibu_daerah" value="{{ old('ibu_daerah', $pendaftaran->ibu_daerah) }}"
                                       class="wizard-input" placeholder="Daerah">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_desa">Desa Sambung</label>
                                <input type="text" name="ibu_desa" id="ibu_desa" value="{{ old('ibu_desa', $pendaftaran->ibu_desa) }}"
                                       class="wizard-input" placeholder="Desa">
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_kelompok">Kelompok Sambung</label>
                                <input type="text" name="ibu_kelompok" id="ibu_kelompok" value="{{ old('ibu_kelompok', $pendaftaran->ibu_kelompok) }}"
                                       class="wizard-input" placeholder="Kelompok">
                            </div>
                        </div>
                    </div>

                    {{-- PENANGGUNG JAWAB BIAYA --}}
                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">
                            Penanggung Jawab Biaya
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="pj_biaya_nama">
                                    Nama Penanggung Jawab Biaya <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="pj_biaya_nama" id="pj_biaya_nama" value="{{ old('pj_biaya_nama', $pendaftaran->pj_biaya_nama) }}"
                                       class="wizard-input @error('pj_biaya_nama') is-invalid @enderror" placeholder="Nama Penanggung Jawab" required>
                                @error('pj_biaya_nama')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="pj_biaya_hp">
                                    No. HP Penanggung Jawab <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="pj_biaya_hp" id="pj_biaya_hp" value="{{ old('pj_biaya_hp', $pendaftaran->pj_biaya_hp) }}"
                                       class="wizard-input @error('pj_biaya_hp') is-invalid @enderror" placeholder="Nomor WhatsApp aktif" required>
                                @error('pj_biaya_hp')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Actions Step 3 --}}
                    <div class="wizard-footer">
                        <button type="button" class="btn-wizard-prev js-btn-prev" data-prev="2">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            Sebelumnya
                        </button>
                        <button type="button" class="btn-wizard-next js-btn-next" data-next="4">
                            Selanjutnya
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ========================================================
                     LANGKAH 04: ALAMAT & KELENGKAPAN BERKAS
                     ======================================================== --}}
                <div class="wizard-step-pane" id="pane_step_4">
                    
                    {{-- ALAMAT TEMPAT TINGGAL --}}
                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">
                            Alamat Tempat Tinggal (Domisili)
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                            <div class="wizard-form-group" style="grid-column: 1 / -1;">
                                <label class="wizard-label" for="alamat_jalan">
                                    Alamat Jalan / Dusun <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="alamat_jalan" id="alamat_jalan" value="{{ old('alamat_jalan', $pendaftaran->alamat_jalan) }}"
                                       class="wizard-input @error('alamat_jalan') is-invalid @enderror" placeholder="Nama Jalan, No. Rumah, RT/RW" required>
                                @error('alamat_jalan')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="rt_rw">
                                    RT / RW <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="rt_rw" id="rt_rw" value="{{ old('rt_rw', $pendaftaran->rt_rw) }}"
                                       class="wizard-input @error('rt_rw') is-invalid @enderror" placeholder="001/002" required>
                                @error('rt_rw')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="desa_kelurahan">
                                    Desa / Kelurahan <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="desa_kelurahan" id="desa_kelurahan" value="{{ old('desa_kelurahan', $pendaftaran->desa_kelurahan) }}"
                                       class="wizard-input @error('desa_kelurahan') is-invalid @enderror" placeholder="Desa / Kelurahan" required>
                                @error('desa_kelurahan')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="kecamatan">
                                    Kecamatan <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="kecamatan" id="kecamatan" value="{{ old('kecamatan', $pendaftaran->kecamatan) }}"
                                       class="wizard-input @error('kecamatan') is-invalid @enderror" placeholder="Kecamatan" required>
                                @error('kecamatan')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="kab_kota">
                                    Kabupaten / Kota <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="kab_kota" id="kab_kota" value="{{ old('kab_kota', $pendaftaran->kab_kota) }}"
                                       class="wizard-input @error('kab_kota') is-invalid @enderror" placeholder="Kabupaten / Kota" required>
                                @error('kab_kota')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="kode_pos">
                                    Kode Pos <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="kode_pos" id="kode_pos" value="{{ old('kode_pos', $pendaftaran->kode_pos) }}"
                                       class="wizard-input @error('kode_pos') is-invalid @enderror" placeholder="63134" required>
                                @error('kode_pos')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="provinsi">
                                    Provinsi <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="provinsi" id="provinsi" value="{{ old('provinsi', $pendaftaran->provinsi) }}"
                                       class="wizard-input @error('provinsi') is-invalid @enderror" placeholder="Provinsi" required>
                                @error('provinsi')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="warga_negara">
                                    Kewarganegaraan <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="warga_negara" id="warga_negara" value="{{ old('warga_negara', $pendaftaran->warga_negara ?? 'Indonesia') }}"
                                       class="wizard-input @error('warga_negara') is-invalid @enderror" placeholder="Indonesia" required>
                                @error('warga_negara')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- STRUKTUR SAMBUNG PENGAJIAN --}}
                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">
                            Struktur Sambung Pengajian
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="kelompok">
                                    Kelompok <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="kelompok" id="kelompok" value="{{ old('kelompok', $pendaftaran->kelompok) }}"
                                       class="wizard-input @error('kelompok') is-invalid @enderror" placeholder="Kelompok Sambung" required>
                                @error('kelompok')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="desa">
                                    Desa <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="desa" id="desa" value="{{ old('desa', $pendaftaran->desa) }}"
                                       class="wizard-input @error('desa') is-invalid @enderror" placeholder="Desa Sambung" required>
                                @error('desa')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="wizard-form-group">
                                <label class="wizard-label" for="daerah">
                                    Daerah <span class="required-mark">*</span>
                                </label>
                                <input type="text" name="daerah" id="daerah" value="{{ old('daerah', $pendaftaran->daerah) }}"
                                       class="wizard-input @error('daerah') is-invalid @enderror" placeholder="Daerah Sambung" required>
                                @error('daerah')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- KELENGKAPAN DOKUMEN / BERKAS --}}
                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">
                            Kelengkapan Dokumen / Berkas
                        </div>

                        @php
                            $berkasItems = [
                                ['flag' => 'berkas_akta', 'foto' => 'foto_akta', 'name' => 'Akta Kelahiran'],
                                ['flag' => 'berkas_kk', 'foto' => 'foto_kk', 'name' => 'Kartu Keluarga (KK)'],
                                ['flag' => 'berkas_ktp_ortu', 'foto' => 'foto_ktp_ortu', 'name' => 'KTP Orang Tua / Wali'],
                                ['flag' => 'berkas_surat_pernyataan', 'foto' => 'foto_surat_pernyataan', 'name' => 'Surat Pernyataan / Rekomendasi'],
                            ];
                        @endphp

                        <div class="gform-grid">
                            @foreach ($berkasItems as $item)
                            <div class="gform-card {{ $pendaftaran->{$item['foto']} ? 'has-file' : '' }}" data-berkas-card>
                                <div class="gform-card-header">
                                    <label class="gform-label">
                                        <input type="checkbox" name="{{ $item['flag'] }}" value="1" data-berkas-check {{ old($item['flag'], $pendaftaran->{$item['flag']}) ? 'checked' : '' }} class="gform-checkbox">
                                        <span class="gform-title">{{ $item['name'] }}</span>
                                    </label>
                                </div>

                                <div class="gform-upload-area">
                                    <input type="file" name="{{ $item['foto'] }}" id="{{ $item['foto'] }}"
                                           accept="image/jpeg,image/png,image/webp" class="upload-input" data-berkas-file>

                                    @if ($pendaftaran->{$item['foto']})
                                    <div style="font-size:12px; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between; background:var(--nav-hover-bg, #f3f4f6); padding:8px 12px; border-radius:8px;">
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" style="width:15px;height:15px;">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <span style="font-weight:600; color:var(--text-primary);">Berkas sudah terunggah</span>
                                        </div>
                                        <a href="{{ asset('storage/' . $pendaftaran->{$item['foto']}) }}" target="_blank" style="color:var(--brand-primary, #2563EB); font-weight:600; text-decoration:underline;">
                                            Lihat Foto
                                        </a>
                                    </div>
                                    @endif

                                    <label for="{{ $item['foto'] }}" class="gform-add-btn" data-upload-drop>
                                        <svg class="gform-upload-icon" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                                        </svg>
                                        <span>{{ $pendaftaran->{$item['foto']} ? 'Ganti file foto' : 'Tambahkan file' }}</span>
                                    </label>

                                    <div class="gform-file-chip" data-upload-preview hidden>
                                        <img src="" alt="" class="gform-chip-thumb" data-upload-img>
                                        <div class="gform-chip-info">
                                            <span class="gform-chip-name" data-upload-name></span>
                                            <span class="gform-chip-size" data-upload-stats></span>
                                        </div>
                                        <button type="button" class="gform-chip-remove" data-upload-remove title="Hapus file" aria-label="Hapus file {{ $item['name'] }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="upload-error" data-upload-error hidden></div>
                                    @error($item['foto'])
                                        <div class="upload-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Actions Step 4 --}}
                    <div class="wizard-footer">
                        <button type="button" class="btn-wizard-prev js-btn-prev" data-prev="3">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            Sebelumnya
                        </button>
                        <button type="submit" class="btn-wizard-next" style="padding: 12px 28px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Simpan Perubahan Formulir
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script src="{{ asset('js/upload-berkas.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentStep = 1;
    const totalSteps = 4;

    const steps = [
        document.getElementById('pane_step_1'),
        document.getElementById('pane_step_2'),
        document.getElementById('pane_step_3'),
        document.getElementById('pane_step_4')
    ];

    const tabSteps = [
        document.getElementById('tab_step_1'),
        document.getElementById('tab_step_2'),
        document.getElementById('tab_step_3'),
        document.getElementById('tab_step_4')
    ];

    function goToStep(stepIndex) {
        if (stepIndex < 1 || stepIndex > totalSteps) return;

        // Update Panes
        steps.forEach((pane, idx) => {
            if (pane) {
                if (idx + 1 === stepIndex) {
                    pane.classList.add('is-active');
                } else {
                    pane.classList.remove('is-active');
                }
            }
        });

        // Update Stepper Tabs
        tabSteps.forEach((tab, idx) => {
            if (tab) {
                const tabStepNum = idx + 1;
                tab.classList.remove('is-active', 'is-completed');

                if (tabStepNum === stepIndex) {
                    tab.classList.add('is-active');
                } else if (tabStepNum < stepIndex) {
                    tab.classList.add('is-completed');
                }
            }
        });

        currentStep = stepIndex;

        // Scroll to top of card smoothly
        const card = document.getElementById('wizardStepper');
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // Step validation check
    function validateStep(stepIndex) {
        const pane = steps[stepIndex - 1];
        if (!pane) return true;

        const requiredInputs = pane.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        let firstInvalid = null;

        requiredInputs.forEach(input => {
            // Check visibility
            if (input.offsetParent === null) return;

            if (!input.value || !input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = input;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid && firstInvalid) {
            firstInvalid.focus();
            if (firstInvalid.scrollIntoView) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return isValid;
    }

    // Click on Stepper tabs
    tabSteps.forEach((tab, idx) => {
        if (tab) {
            tab.addEventListener('click', function () {
                const targetStep = idx + 1;
                if (targetStep < currentStep) {
                    goToStep(targetStep);
                } else if (targetStep > currentStep) {
                    let canProceed = true;
                    for (let s = currentStep; s < targetStep; s++) {
                        if (!validateStep(s)) {
                            canProceed = false;
                            break;
                        }
                    }
                    if (canProceed) {
                        goToStep(targetStep);
                    }
                }
            });
        }
    });

    // Next button clicks
    document.querySelectorAll('.js-btn-next').forEach(btn => {
        btn.addEventListener('click', function () {
            const nextStep = parseInt(this.getAttribute('data-next'), 10);
            if (validateStep(currentStep)) {
                goToStep(nextStep);
            }
        });
    });

    // Prev button clicks
    document.querySelectorAll('.js-btn-prev').forEach(btn => {
        btn.addEventListener('click', function () {
            const prevStep = parseInt(this.getAttribute('data-prev'), 10);
            goToStep(prevStep);
        });
    });

    // Dynamic Jenjang & Sekolah Asal & NISN mark
    const selectJenjang = document.getElementById('jenjang');
    const labelSekolahAsal = document.getElementById('label_nama_sekolah_asal');
    const inputSekolahAsal = document.getElementById('nama_sekolah_asal');
    const markNisn = document.getElementById('mark_nisn');
    const selectPendidikan = document.getElementById('pendidikan_terakhir');

    function handleJenjangChange(val, isUser = false) {
        if (val === 'SMP') {
            if (labelSekolahAsal) labelSekolahAsal.innerHTML = 'Nama SD / MI Asal <span class="required-mark">*</span>';
            if (inputSekolahAsal) inputSekolahAsal.placeholder = 'Contoh: SDN 1 Bojonegoro / MI Al Hidayah';
            if (markNisn) markNisn.style.display = 'inline';
            if (isUser && selectPendidikan && (!selectPendidikan.value || selectPendidikan.value === 'SMP/MTs')) {
                selectPendidikan.value = 'SD/MI';
            }
        } else if (val === 'SMA') {
            if (labelSekolahAsal) labelSekolahAsal.innerHTML = 'Nama SMP / MTs Asal <span class="required-mark">*</span>';
            if (inputSekolahAsal) inputSekolahAsal.placeholder = 'Contoh: SMPN 2 Madiun / MTs Negeri 1';
            if (markNisn) markNisn.style.display = 'inline';
            if (isUser && selectPendidikan && (!selectPendidikan.value || selectPendidikan.value === 'SD/MI')) {
                selectPendidikan.value = 'SMP/MTs';
            }
        } else {
            if (labelSekolahAsal) labelSekolahAsal.innerHTML = 'Nama Sekolah Asal';
            if (inputSekolahAsal) inputSekolahAsal.placeholder = 'Nama sekolah asal (opsional)';
            if (markNisn) markNisn.style.display = 'none';
        }
    }

    if (selectJenjang) {
        selectJenjang.addEventListener('change', function () {
            handleJenjangChange(this.value, true);
        });
        if (selectJenjang.value) {
            handleJenjangChange(selectJenjang.value, false);
        }
    }

    // Dynamic KIP
    const selectKip = document.getElementById('penerima_kip');
    const wrapperKip = document.getElementById('wrapper_nomor_kip');
    const inputNomorKip = document.getElementById('nomor_kip');

    if (selectKip) {
        selectKip.addEventListener('change', function () {
            if (this.value === '1') {
                if (wrapperKip) wrapperKip.style.display = 'flex';
                if (inputNomorKip) inputNomorKip.focus();
            } else {
                if (wrapperKip) wrapperKip.style.display = 'none';
                if (inputNomorKip) inputNomorKip.value = '';
            }
        });
    }

    // Auto-jump to step with error if server-side validation failed
    @if ($errors->any())
        @php
            $errorKeys = $errors->keys();
            $step1Fields = ['jenjang', 'nik', 'nisn', 'no_induk'];
            $step2Fields = ['nama_lengkap', 'nama_panggilan', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'anak_ke', 'jumlah_saudara', 'tinggal_bersama', 'mulai_pengajian', 'status_mondok', 'pendidikan_terakhir', 'nama_sekolah_asal', 'jurusan', 'tahun_lulus', 'penerima_kip', 'nomor_kip', 'penyakit_diderita', 'alergi', 'gol_darah', 'tinggi_badan', 'berat_badan', 'keahlian_prestasi'];
            $step3Fields = ['ayah_nama', 'ayah_status', 'ayah_ttl', 'ayah_pendidikan', 'ayah_pekerjaan', 'ayah_penghasilan', 'ayah_agama', 'ayah_alamat', 'ayah_wn', 'ayah_hp', 'ayah_daerah', 'ayah_desa', 'ayah_kelompok', 'ibu_nama', 'ibu_status', 'ibu_ttl', 'ibu_pendidikan', 'ibu_pekerjaan', 'ibu_penghasilan', 'ibu_agama', 'ibu_alamat', 'ibu_wn', 'ibu_hp', 'ibu_daerah', 'ibu_desa', 'ibu_kelompok', 'pj_biaya_nama', 'pj_biaya_hp'];
            $step4Fields = ['alamat_jalan', 'rt_rw', 'desa_kelurahan', 'kecamatan', 'kab_kota', 'kode_pos', 'provinsi', 'warga_negara', 'kelompok', 'desa', 'daerah', 'berkas_akta', 'berkas_kk', 'berkas_ktp_ortu', 'berkas_surat_pernyataan', 'foto_akta', 'foto_kk', 'foto_ktp_ortu', 'foto_surat_pernyataan'];

            $targetStep = 1;
            foreach ($errorKeys as $k) {
                if (in_array($k, $step1Fields)) { $targetStep = 1; break; }
                if (in_array($k, $step2Fields)) { $targetStep = 2; break; }
                if (in_array($k, $step3Fields)) { $targetStep = 3; break; }
                if (in_array($k, $step4Fields)) { $targetStep = 4; break; }
            }
        @endphp
        goToStep({{ $targetStep }});
    @endif
});
</script>
@endsection
