@extends('spmb.layout')

@section('title', $penerimaan->judul)

@section('content')

@if (session('error'))
<div class="alert-banner alert-error" style="margin-bottom:18px;">
    <div>{{ session('error') }}</div>
</div>
@endif

@if (!$isOpen)
    <div class="spmb-card" style="max-width:620px; margin:24px auto; text-align:center; padding:36px 24px;">
        <div style="margin-bottom:14px;">
            <span class="status-pill status-ditolak" style="font-size:13px; padding:6px 16px;">{{ $statusState['label'] }}</span>
        </div>
        <h1 style="font-size:22px; font-weight:800; color:#0F172A; margin:0 0 18px 0;">{{ $penerimaan->judul }}</h1>

        @if($penerimaan->gelombangs->isNotEmpty())
        <div style="text-align:left; border:1px solid #E5E7EB; border-radius:12px; overflow:hidden; margin-bottom:20px;">
            <table class="modern-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Gelombang</th>
                        <th>Jadwal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($penerimaan->gelombangs as $gel)
                    <tr>
                        <td style="font-weight:600;">{{ $gel->nama_gelombang }}</td>
                        <td>{{ $gel->tanggal_mulai->translatedFormat('d M Y') }} – {{ $gel->tanggal_selesai->translatedFormat('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('spmb.cek-status', $penerimaan->slug) }}" class="btn btn-primary">Cek Status Pendaftaran</a>
            @if($penerimaan->waUrl())
                <a href="{{ $penerimaan->waUrl() }}" target="_blank" class="btn btn-secondary">Hubungi Panitia</a>
            @endif
        </div>
    </div>
@else
    {{-- Ringkasan Info Event --}}
    <div class="spmb-meta-strip">
        @if($activeGelombang)
            <span class="spmb-chip spmb-chip-primary">{{ $activeGelombang->nama_gelombang }} · s/d {{ $activeGelombang->tanggal_selesai->translatedFormat('d M Y') }}</span>
        @else
            <span class="spmb-chip spmb-chip-primary">Pendaftaran Dibuka</span>
        @endif
        <span class="spmb-chip">Jenjang: {{ $penerimaan->jenjang }}</span>
        @if($penerimaan->jadwal_seleksi)
            <span class="spmb-chip">Seleksi: {{ $penerimaan->jadwal_seleksi }}</span>
        @endif
        @if($penerimaan->lokasi_seleksi)
            <span class="spmb-chip">{{ $penerimaan->lokasi_seleksi }}</span>
        @endif
    </div>

    @php $syaratList = $penerimaan->syaratList(); @endphp
    @if(!empty($syaratList))
    <div class="spmb-card" style="padding:14px 18px; margin-bottom:18px;">
        <div style="font-size:13px; font-weight:700; color:#0F172A; margin-bottom:6px;">Persyaratan</div>
        <ul style="margin:0; padding-left:18px; font-size:13px; color:#334155; display:flex; flex-direction:column; gap:3px;">
            @foreach($syaratList as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert-banner alert-error" style="margin-bottom: 18px;">
        <div>
            <ul style="margin: 0 0 0 18px; font-size: 13px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ route('spmb.store', $penerimaan->slug) }}" method="POST" enctype="multipart/form-data" id="formSpmbPublic">
        @csrf
        {{-- Honeypot anti-bot --}}
        <div style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
            <input type="text" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <div class="wizard-card">
            <div class="wizard-stepper" id="wizardStepper">
                <div class="wizard-step is-active" data-step="1" id="tab_step_1">
                    <div class="wizard-step-circle">01</div>
                    <div class="wizard-step-title">Identitas</div>
                </div>
                <div class="wizard-divider" aria-hidden="true">
                    <svg viewBox="0 0 16 60" fill="none" preserveAspectRatio="none"><path d="M0 0 L14 30 L0 60" stroke="currentColor" stroke-width="1.5" /></svg>
                </div>
                <div class="wizard-step" data-step="2" id="tab_step_2">
                    <div class="wizard-step-circle">02</div>
                    <div class="wizard-step-title">Data Pribadi</div>
                </div>
                <div class="wizard-divider" aria-hidden="true">
                    <svg viewBox="0 0 16 60" fill="none" preserveAspectRatio="none"><path d="M0 0 L14 30 L0 60" stroke="currentColor" stroke-width="1.5" /></svg>
                </div>
                <div class="wizard-step" data-step="3" id="tab_step_3">
                    <div class="wizard-step-circle">03</div>
                    <div class="wizard-step-title">Keluarga</div>
                </div>
                <div class="wizard-divider" aria-hidden="true">
                    <svg viewBox="0 0 16 60" fill="none" preserveAspectRatio="none"><path d="M0 0 L14 30 L0 60" stroke="currentColor" stroke-width="1.5" /></svg>
                </div>
                <div class="wizard-step" data-step="4" id="tab_step_4">
                    <div class="wizard-step-circle">04</div>
                    <div class="wizard-step-title">Alamat & Berkas</div>
                </div>
            </div>

            <div class="wizard-body">

                {{-- STEP 1: IDENTITAS --}}
                <div class="wizard-step-pane is-active" id="pane_step_1">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="jenjang">Jenjang <span style="color:#EF4444;">*</span></label>
                            @if(count($allowedJenjangs) === 1)
                                <input type="hidden" name="jenjang" value="{{ $allowedJenjangs[0] }}">
                                <input type="text" class="wizard-input" value="{{ $allowedJenjangs[0] }}" readonly style="background:#F8FAFC;">
                            @else
                                <select name="jenjang" id="jenjang" class="wizard-select @error('jenjang') is-invalid @enderror" required>
                                    <option value="">Pilih Jenjang</option>
                                    @foreach($allowedJenjangs as $j)
                                        <option value="{{ $j }}" {{ old('jenjang') === $j ? 'selected' : '' }}>{{ $j }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('jenjang')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nama_lengkap">Nama Lengkap <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap') }}"
                                   class="wizard-input @error('nama_lengkap') is-invalid @enderror" placeholder="Sesuai KK / Akta" required>
                            @error('nama_lengkap')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="jenis_kelamin">Jenis Kelamin <span style="color:#EF4444;">*</span></label>
                            <select name="jenis_kelamin" id="jenis_kelamin" class="wizard-select @error('jenis_kelamin') is-invalid @enderror" required>
                                <option value="">Pilih</option>
                                <option value="Laki-laki" {{ old('jenis_kelamin') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="Perempuan" {{ old('jenis_kelamin') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('jenis_kelamin')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="pj_biaya_hp">No. WhatsApp Aktif (Orang Tua / Wali) <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="pj_biaya_hp" id="pj_biaya_hp" value="{{ old('pj_biaya_hp') }}"
                                   class="wizard-input @error('pj_biaya_hp') is-invalid @enderror" placeholder="08xxxxxxxxxx" required>
                            @error('pj_biaya_hp')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nik">NIK (16 Digit)</label>
                            <input type="text" name="nik" id="nik" maxlength="16" value="{{ old('nik') }}"
                                   class="wizard-input @error('nik') is-invalid @enderror" placeholder="Nomor Induk Kependudukan">
                            @error('nik')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nisn">NISN</label>
                            <input type="text" name="nisn" id="nisn" maxlength="20" value="{{ old('nisn') }}"
                                   class="wizard-input @error('nisn') is-invalid @enderror" placeholder="Nomor Induk Siswa Nasional">
                            @error('nisn')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="wizard-footer" style="justify-content:flex-end;">
                        <button type="button" class="btn-wizard-next js-btn-next" data-next="2">
                            Selanjutnya
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                </div>

                {{-- STEP 2: DATA PRIBADI --}}
                <div class="wizard-step-pane" id="pane_step_2">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:18px; margin-bottom:22px;">
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nama_panggilan">Nama Panggilan</label>
                            <input type="text" name="nama_panggilan" id="nama_panggilan" value="{{ old('nama_panggilan') }}" class="wizard-input" placeholder="Nama Panggilan">
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tempat_lahir">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="tempat_lahir" value="{{ old('tempat_lahir') }}" class="wizard-input" placeholder="Kota / Kabupaten">
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tanggal_lahir">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="wizard-input">
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="anak_ke">Anak Ke-</label>
                            <input type="number" name="anak_ke" id="anak_ke" min="1" value="{{ old('anak_ke') }}" class="wizard-input" placeholder="1">
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="jumlah_saudara">Jumlah Saudara</label>
                            <input type="number" name="jumlah_saudara" id="jumlah_saudara" min="0" value="{{ old('jumlah_saudara') }}" class="wizard-input" placeholder="0">
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tinggal_bersama">Tinggal Bersama</label>
                            <select name="tinggal_bersama" id="tinggal_bersama" class="wizard-select">
                                <option value="">Pilih</option>
                                @foreach (['Orang Tua Kandung', 'Ayah', 'Ibu', 'Wali'] as $tb)
                                    <option value="{{ $tb }}" {{ old('tinggal_bersama') === $tb ? 'selected' : '' }}>{{ $tb }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="mulai_pengajian">Mulai Sambung Pengajian (Tahun)</label>
                            <input type="text" name="mulai_pengajian" id="mulai_pengajian" value="{{ old('mulai_pengajian') }}" class="wizard-input" placeholder="2024">
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="status_mondok">Status Mondok</label>
                            <select name="status_mondok" id="status_mondok" class="wizard-select">
                                <option value="Person" {{ old('status_mondok', 'Person') === 'Person' ? 'selected' : '' }}>Person (Mandiri)</option>
                                <option value="Utusan/Kiriman" {{ old('status_mondok') === 'Utusan/Kiriman' ? 'selected' : '' }}>Utusan / Kiriman</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:18px; margin-bottom:22px; padding-top:18px; border-top:1px solid #F3F4F6;">
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="pendidikan_terakhir">Pendidikan Terakhir</label>
                            <select name="pendidikan_terakhir" id="pendidikan_terakhir" class="wizard-select">
                                <option value="">Pilih</option>
                                @foreach (['SD/MI', 'SMP/MTs', 'SMA/MA/SMK', 'D3', 'S1/D4', 'Lainnya'] as $pddk)
                                    <option value="{{ $pddk }}" {{ old('pendidikan_terakhir') === $pddk ? 'selected' : '' }}>{{ $pddk }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="nama_sekolah_asal">Nama Sekolah Asal</label>
                            <input type="text" name="nama_sekolah_asal" id="nama_sekolah_asal" value="{{ old('nama_sekolah_asal') }}" class="wizard-input" placeholder="Nama Sekolah Asal">
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tahun_lulus">Tahun Lulus</label>
                            <input type="text" name="tahun_lulus" id="tahun_lulus" value="{{ old('tahun_lulus') }}" class="wizard-input" placeholder="2027">
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="penerima_kip">Penerima KIP / PIP / PKH</label>
                            <select name="penerima_kip" id="penerima_kip" class="wizard-select">
                                <option value="0" {{ old('penerima_kip', '0') == '0' ? 'selected' : '' }}>Tidak</option>
                                <option value="1" {{ old('penerima_kip') == '1' ? 'selected' : '' }}>Ya</option>
                            </select>
                        </div>

                        <div class="wizard-form-group" id="wrapper_nomor_kip" style="{{ old('penerima_kip') == '1' ? 'display:flex;' : 'display:none;' }}">
                            <label class="wizard-label" for="nomor_kip">Nomor KIP / KKS / PKH</label>
                            <input type="text" name="nomor_kip" id="nomor_kip" value="{{ old('nomor_kip') }}" class="wizard-input">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:18px; padding-top:18px; border-top:1px solid #F3F4F6;">
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="penyakit_diderita">Riwayat Penyakit</label>
                            <input type="text" name="penyakit_diderita" id="penyakit_diderita" value="{{ old('penyakit_diderita') }}" class="wizard-input" placeholder="Jika ada">
                        </div>
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="alergi">Alergi</label>
                            <input type="text" name="alergi" id="alergi" value="{{ old('alergi') }}" class="wizard-input" placeholder="Jika ada">
                        </div>
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="gol_darah">Golongan Darah</label>
                            <select name="gol_darah" id="gol_darah" class="wizard-select">
                                <option value="">Pilih</option>
                                @foreach (['A', 'B', 'AB', 'O', 'Tidak Tahu'] as $gd)
                                    <option value="{{ $gd }}" {{ old('gol_darah') === $gd ? 'selected' : '' }}>{{ $gd }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tinggi_badan">Tinggi Badan (cm)</label>
                            <input type="number" name="tinggi_badan" id="tinggi_badan" value="{{ old('tinggi_badan') }}" class="wizard-input">
                        </div>
                        <div class="wizard-form-group">
                            <label class="wizard-label" for="berat_badan">Berat Badan (kg)</label>
                            <input type="number" name="berat_badan" id="berat_badan" value="{{ old('berat_badan') }}" class="wizard-input">
                        </div>
                        <div class="wizard-form-group" style="grid-column: 1 / -1;">
                            <label class="wizard-label" for="keahlian_prestasi">Keahlian / Prestasi</label>
                            <textarea name="keahlian_prestasi" id="keahlian_prestasi" rows="2" class="wizard-textarea">{{ old('keahlian_prestasi') }}</textarea>
                        </div>
                    </div>

                    <div class="wizard-footer">
                        <button type="button" class="btn-wizard-prev js-btn-prev" data-prev="1">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            Sebelumnya
                        </button>
                        <button type="button" class="btn-wizard-next js-btn-next" data-next="3">
                            Selanjutnya
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                </div>

                {{-- STEP 3: KELUARGA --}}
                <div class="wizard-step-pane" id="pane_step_3">
                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">Data Ayah</div>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_nama">Nama Ayah</label>
                                <input type="text" name="ayah_nama" id="ayah_nama" value="{{ old('ayah_nama') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_status">Status Ayah</label>
                                <select name="ayah_status" id="ayah_status" class="wizard-select">
                                    <option value="Hidup" {{ old('ayah_status', 'Hidup') === 'Hidup' ? 'selected' : '' }}>Hidup</option>
                                    <option value="Meninggal" {{ old('ayah_status') === 'Meninggal' ? 'selected' : '' }}>Meninggal</option>
                                </select>
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_ttl">Tempat, Tanggal Lahir</label>
                                <input type="text" name="ayah_ttl" id="ayah_ttl" value="{{ old('ayah_ttl') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_pendidikan">Pendidikan Terakhir</label>
                                <input type="text" name="ayah_pendidikan" id="ayah_pendidikan" value="{{ old('ayah_pendidikan') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_pekerjaan">Pekerjaan</label>
                                <input type="text" name="ayah_pekerjaan" id="ayah_pekerjaan" value="{{ old('ayah_pekerjaan') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_penghasilan">Penghasilan</label>
                                <input type="text" name="ayah_penghasilan" id="ayah_penghasilan" value="{{ old('ayah_penghasilan') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_hp">No. HP / WA Ayah</label>
                                <input type="text" name="ayah_hp" id="ayah_hp" value="{{ old('ayah_hp') }}" class="wizard-input" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_daerah">Daerah Sambung</label>
                                <input type="text" name="ayah_daerah" id="ayah_daerah" value="{{ old('ayah_daerah') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_desa">Desa Sambung</label>
                                <input type="text" name="ayah_desa" id="ayah_desa" value="{{ old('ayah_desa') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ayah_kelompok">Kelompok Sambung</label>
                                <input type="text" name="ayah_kelompok" id="ayah_kelompok" value="{{ old('ayah_kelompok') }}" class="wizard-input">
                            </div>
                        </div>
                    </div>

                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">Data Ibu</div>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_nama">Nama Ibu</label>
                                <input type="text" name="ibu_nama" id="ibu_nama" value="{{ old('ibu_nama') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_status">Status Ibu</label>
                                <select name="ibu_status" id="ibu_status" class="wizard-select">
                                    <option value="Hidup" {{ old('ibu_status', 'Hidup') === 'Hidup' ? 'selected' : '' }}>Hidup</option>
                                    <option value="Meninggal" {{ old('ibu_status') === 'Meninggal' ? 'selected' : '' }}>Meninggal</option>
                                </select>
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_ttl">Tempat, Tanggal Lahir</label>
                                <input type="text" name="ibu_ttl" id="ibu_ttl" value="{{ old('ibu_ttl') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_pendidikan">Pendidikan Terakhir</label>
                                <input type="text" name="ibu_pendidikan" id="ibu_pendidikan" value="{{ old('ibu_pendidikan') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_pekerjaan">Pekerjaan</label>
                                <input type="text" name="ibu_pekerjaan" id="ibu_pekerjaan" value="{{ old('ibu_pekerjaan') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_penghasilan">Penghasilan</label>
                                <input type="text" name="ibu_penghasilan" id="ibu_penghasilan" value="{{ old('ibu_penghasilan') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_hp">No. HP / WA Ibu</label>
                                <input type="text" name="ibu_hp" id="ibu_hp" value="{{ old('ibu_hp') }}" class="wizard-input" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_daerah">Daerah Sambung</label>
                                <input type="text" name="ibu_daerah" id="ibu_daerah" value="{{ old('ibu_daerah') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_desa">Desa Sambung</label>
                                <input type="text" name="ibu_desa" id="ibu_desa" value="{{ old('ibu_desa') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="ibu_kelompok">Kelompok Sambung</label>
                                <input type="text" name="ibu_kelompok" id="ibu_kelompok" value="{{ old('ibu_kelompok') }}" class="wizard-input">
                            </div>
                        </div>
                    </div>

                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">Penanggung Jawab Biaya</div>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="pj_biaya_nama">Nama Penanggung Jawab</label>
                                <input type="text" name="pj_biaya_nama" id="pj_biaya_nama" value="{{ old('pj_biaya_nama') }}" class="wizard-input">
                            </div>
                        </div>
                    </div>

                    <div class="wizard-footer">
                        <button type="button" class="btn-wizard-prev js-btn-prev" data-prev="2">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            Sebelumnya
                        </button>
                        <button type="button" class="btn-wizard-next js-btn-next" data-next="4">
                            Selanjutnya
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                </div>

                {{-- STEP 4: ALAMAT & BERKAS --}}
                <div class="wizard-step-pane" id="pane_step_4">
                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">Alamat Domisili</div>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                            <div class="wizard-form-group" style="grid-column: 1 / -1;">
                                <label class="wizard-label" for="alamat_jalan">Alamat Jalan / Dusun</label>
                                <input type="text" name="alamat_jalan" id="alamat_jalan" value="{{ old('alamat_jalan') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="rt_rw">RT / RW</label>
                                <input type="text" name="rt_rw" id="rt_rw" value="{{ old('rt_rw') }}" class="wizard-input" placeholder="001/002">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="desa_kelurahan">Desa / Kelurahan</label>
                                <input type="text" name="desa_kelurahan" id="desa_kelurahan" value="{{ old('desa_kelurahan') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="kecamatan">Kecamatan</label>
                                <input type="text" name="kecamatan" id="kecamatan" value="{{ old('kecamatan') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="kab_kota">Kabupaten / Kota</label>
                                <input type="text" name="kab_kota" id="kab_kota" value="{{ old('kab_kota') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="kode_pos">Kode Pos</label>
                                <input type="text" name="kode_pos" id="kode_pos" value="{{ old('kode_pos') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="provinsi">Provinsi</label>
                                <input type="text" name="provinsi" id="provinsi" value="{{ old('provinsi') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="warga_negara">Kewarganegaraan</label>
                                <input type="text" name="warga_negara" id="warga_negara" value="{{ old('warga_negara', 'Indonesia') }}" class="wizard-input">
                            </div>
                        </div>
                    </div>

                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">Struktur Sambung Pengajian</div>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="kelompok">Kelompok</label>
                                <input type="text" name="kelompok" id="kelompok" value="{{ old('kelompok') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="desa">Desa</label>
                                <input type="text" name="desa" id="desa" value="{{ old('desa') }}" class="wizard-input">
                            </div>
                            <div class="wizard-form-group">
                                <label class="wizard-label" for="daerah">Daerah</label>
                                <input type="text" name="daerah" id="daerah" value="{{ old('daerah') }}" class="wizard-input">
                            </div>
                        </div>
                    </div>

                    @php
                        $visibleDokumen = [];
                        foreach (\App\Models\PenerimaanSantri::DOKUMEN_META as $flagKey => $meta) {
                            $mode = $dokumenConfig[$flagKey] ?? 'opsional';
                            if ($mode !== 'tidak') {
                                $visibleDokumen[] = [
                                    'flag'     => $flagKey,
                                    'foto'     => $meta['foto'],
                                    'name'     => $meta['label'],
                                    'required' => $mode === 'wajib',
                                ];
                            }
                        }
                    @endphp

                    @if(!empty($visibleDokumen))
                    <div class="wizard-subcard">
                        <div class="wizard-subcard-title">Dokumen</div>
                        <div class="gform-grid">
                            @foreach ($visibleDokumen as $item)
                            <div class="gform-card" data-berkas-card>
                                <div class="gform-card-header">
                                    <label class="gform-label">
                                        <input type="checkbox" name="{{ $item['flag'] }}" value="1" data-berkas-check {{ old($item['flag']) ? 'checked' : '' }} class="gform-checkbox">
                                        <span class="gform-title">{{ $item['name'] }} @if($item['required'])<span style="color:#EF4444;">*</span>@endif</span>
                                    </label>
                                </div>
                                <div class="gform-upload-area">
                                    <input type="file" name="{{ $item['foto'] }}" id="{{ $item['foto'] }}"
                                           accept="image/jpeg,image/png,image/webp" class="upload-input" data-berkas-file
                                           @if($item['required']) required @endif>
                                    <label for="{{ $item['foto'] }}" class="gform-add-btn" data-upload-drop>
                                        <svg class="gform-upload-icon" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                                        </svg>
                                        <span>Unggah foto</span>
                                    </label>
                                    <div class="gform-file-chip" data-upload-preview hidden>
                                        <img src="" alt="" class="gform-chip-thumb" data-upload-img>
                                        <div class="gform-chip-info">
                                            <span class="gform-chip-name" data-upload-name></span>
                                            <span class="gform-chip-size" data-upload-stats></span>
                                        </div>
                                        <button type="button" class="gform-chip-remove" data-upload-remove title="Hapus file">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        </button>
                                    </div>
                                    <div class="upload-error" data-upload-error hidden></div>
                                    @error($item['foto'])<div class="upload-error">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="wizard-footer">
                        <button type="button" class="btn-wizard-prev js-btn-prev" data-prev="3">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            Sebelumnya
                        </button>
                        <button type="submit" class="btn-wizard-next" style="padding:12px 28px;">
                            Kirim Pendaftaran
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
@endif

@endsection

@if($isOpen)
@push('scripts')
<script src="{{ asset('js/upload-berkas.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var currentStep = 1;
    var panes = [1, 2, 3, 4].map(function (i) { return document.getElementById('pane_step_' + i); });
    var tabs = [1, 2, 3, 4].map(function (i) { return document.getElementById('tab_step_' + i); });

    function goToStep(step) {
        if (step < 1 || step > 4) return;
        panes.forEach(function (p, idx) {
            if (p) p.classList.toggle('is-active', idx + 1 === step);
        });
        tabs.forEach(function (t, idx) {
            if (!t) return;
            t.classList.remove('is-active', 'is-completed');
            if (idx + 1 === step) t.classList.add('is-active');
            else if (idx + 1 < step) t.classList.add('is-completed');
        });
        currentStep = step;
        var stepper = document.getElementById('wizardStepper');
        if (stepper) stepper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function validateStep1() {
        var requiredIds = ['jenjang', 'nama_lengkap', 'jenis_kelamin', 'pj_biaya_hp'];
        for (var i = 0; i < requiredIds.length; i++) {
            var el = document.getElementById(requiredIds[i]);
            if (el && el.hasAttribute('required') && !String(el.value || '').trim()) {
                el.focus();
                el.reportValidity();
                return false;
            }
        }
        return true;
    }

    tabs.forEach(function (t, idx) {
        if (t) t.addEventListener('click', function () {
            var target = idx + 1;
            if (target > 1 && currentStep === 1 && !validateStep1()) return;
            goToStep(target);
        });
    });

    document.querySelectorAll('.js-btn-next').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (currentStep === 1 && !validateStep1()) return;
            goToStep(parseInt(this.getAttribute('data-next'), 10));
        });
    });

    document.querySelectorAll('.js-btn-prev').forEach(function (btn) {
        btn.addEventListener('click', function () {
            goToStep(parseInt(this.getAttribute('data-prev'), 10));
        });
    });

    var selectKip = document.getElementById('penerima_kip');
    var wrapperKip = document.getElementById('wrapper_nomor_kip');
    if (selectKip && wrapperKip) {
        selectKip.addEventListener('change', function () {
            wrapperKip.style.display = this.value === '1' ? 'flex' : 'none';
        });
    }

    @if ($errors->any())
        @php
            $errorKeys = $errors->keys();
            $step1Fields = ['jenjang', 'nama_lengkap', 'jenis_kelamin', 'pj_biaya_hp', 'nik', 'nisn'];
            $step2Fields = ['nama_panggilan', 'tempat_lahir', 'tanggal_lahir', 'anak_ke', 'jumlah_saudara', 'tinggal_bersama', 'mulai_pengajian', 'status_mondok', 'pendidikan_terakhir', 'nama_sekolah_asal', 'tahun_lulus', 'penerima_kip', 'nomor_kip', 'penyakit_diderita', 'alergi', 'gol_darah', 'tinggi_badan', 'berat_badan', 'keahlian_prestasi'];
            $step3Fields = ['ayah_nama', 'ayah_status', 'ayah_ttl', 'ayah_pendidikan', 'ayah_pekerjaan', 'ayah_penghasilan', 'ayah_hp', 'ayah_daerah', 'ayah_desa', 'ayah_kelompok', 'ibu_nama', 'ibu_status', 'ibu_ttl', 'ibu_pendidikan', 'ibu_pekerjaan', 'ibu_penghasilan', 'ibu_hp', 'ibu_daerah', 'ibu_desa', 'ibu_kelompok', 'pj_biaya_nama'];
            $targetStep = 4;
            foreach ($errorKeys as $k) {
                if (in_array($k, $step1Fields, true)) { $targetStep = 1; break; }
                if (in_array($k, $step2Fields, true)) { $targetStep = 2; break; }
                if (in_array($k, $step3Fields, true)) { $targetStep = 3; break; }
            }
        @endphp
        goToStep({{ $targetStep }});
    @endif
});
</script>
@endpush
@endif
