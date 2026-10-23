<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pendaftaran - {{ $pendaftaran->nama_lengkap }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #d5dbe5;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ===== Toolbar (tidak ikut tercetak) ===== */
        .toolbar {
            position: sticky; top: 0; z-index: 20;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
            padding: 12px 24px;
            background: #0f172a; color: #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,.25);
        }
        .toolbar-title { font-size: 14px; font-weight: 700; }
        .toolbar-title small { display: block; font-size: 12px; font-weight: 400; color: #94a3b8; }
        .toolbar-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .tb-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 18px; font-size: 13px; font-weight: 600; font-family: inherit;
            border-radius: 9px; border: 1px solid #334155; background: #1e293b; color: #fff;
            cursor: pointer; text-decoration: none; transition: .15s;
        }
        .tb-btn:hover { background: #334155; }
        .tb-btn.primary { background: #0052ff; border-color: #0052ff; }
        .tb-btn.primary:hover { background: #0043d4; }
        .tb-btn.success { background: #059669; border-color: #059669; }
        .tb-btn.success:hover { background: #047857; }
        .tb-btn svg { width: 16px; height: 16px; }

        /* ===== Kertas A4 ===== */
        .sheet-wrap { padding: 24px 0 48px; overflow-x: auto; }
        .paper {
            width: 210mm; min-height: 296mm;
            margin: 0 auto; background: #fff;
            padding: 7mm 13mm 5mm;
            font-size: 8.8pt; line-height: 1.25;
            box-shadow: 0 10px 40px rgba(15,23,42,.25);
        }

        /* ===== Kop surat ===== */
        .kop { display: flex; align-items: center; gap: 3mm; }
        .kop-logo { width: 23mm; height: 23mm; object-fit: contain; flex: none; }
        .kop-text { flex: 1; text-align: center; font-family: "Times New Roman", Times, serif; }
        .kop-text .nama { font-size: 14.5pt; font-weight: 700; line-height: 1.15; }
        .kop-text .alamat { font-size: 9pt; margin-top: 1mm; }
        .kop-text .email { color: #1d4ed8; text-decoration: underline; }
        .garis-tebal { height: 0.9mm; background: #000; margin-top: 1.5mm; }
        .garis-tipis { height: 0.25mm; background: #000; margin-top: 0.6mm; }

        .judul {
            text-align: center; font-weight: 700; font-size: 11pt;
            text-decoration: underline; margin-top: 3mm; letter-spacing: .2px;
        }

        .no-induk-row { display: flex; justify-content: flex-end; align-items: center; gap: 2mm; margin-top: 1.5mm; }
        .no-induk-box { display: flex; border: 1px solid #000; height: 5.2mm; }
        .no-induk-box span { width: 17mm; text-align: center; font-weight: 700; line-height: 4.8mm; border-right: 1px solid #000; }
        .no-induk-box span:last-child { border-right: none; }
        .no-induk-note { font-size: 7.5pt; }

        /* ===== Baris isian ===== */
        .sec { font-weight: 700; font-size: 9.6pt; margin-top: 2.6mm; }
        .ind { padding-left: 6mm; }
        .r { display: flex; align-items: flex-end; margin-top: 0.8mm; min-height: 4mm; }
        .n  { flex: none; width: 5.5mm; }
        .l  { flex: none; width: var(--lw, 38mm); }
        .c  { flex: none; width: 2.5mm; text-align: center; }
        .sp { flex: none; width: 43.5mm; }
        .gap { flex: none; width: 3mm; }
        .t  { flex: none; white-space: nowrap; }
        .v  {
            flex: 1; min-width: 0;
            border-bottom: 1px dotted #000;
            padding: 0 1mm; min-height: 4mm;
            font-weight: 700;
        }
        .w-sm { flex: 0 0 40mm; }

        .box {
            display: inline-block; width: 3.3mm; height: 3.3mm;
            border: 1px solid #000; text-align: center;
            line-height: 2.9mm; font-size: 8.5pt; font-weight: 900;
            vertical-align: -0.6mm; margin-right: 1mm;
        }

        /* NIK */
        .nik { display: flex; align-items: flex-end; }
        .nik .grp { display: flex; }
        .nik .cell {
            width: 4.7mm; height: 5mm; border: 1px solid #000; margin-right: -1px;
            text-align: center; font-weight: 700; line-height: 4.5mm;
        }
        .nik .dash { width: 3.5mm; text-align: center; font-weight: 700; }

        /* ===== Orang tua ===== */
        .ortu { display: grid; grid-template-columns: 1fr 1fr; column-gap: 8mm; }
        .ortu .l { --lw: 33mm; }
        .ortu .n { width: 5mm; }
        .ortu .sp { width: 5mm; }
        .strike { text-decoration: line-through; color: #555; }
        .sel { font-weight: 700; text-decoration: underline; }

        /* ===== Persyaratan ===== */
        .syarat { display: grid; grid-template-columns: 1fr 1fr; column-gap: 8mm; margin-top: 1mm; }
        .syarat .r { align-items: flex-start; }
        .syarat .lbl { flex: 1; padding-right: 2mm; }
        .syarat .opts { flex: none; white-space: nowrap; }

        /* ===== Tanda tangan ===== */
        .ttd { display: grid; grid-template-columns: 1fr 1fr 1fr; margin-top: 3mm; text-align: center; }
        .ttd .ruang { height: 14mm; }
        .ttd .kurung { letter-spacing: .3px; }

        /* ===== Cetak ===== */
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet-wrap { padding: 0; overflow: visible; }
            .paper { box-shadow: none; margin: 0; width: 210mm; }
        }
    </style>
</head>
<body>
@php
    $p = $pendaftaran;
    $ck = fn ($cond) => $cond ? '✓' : '&nbsp;';
    $tgl = fn ($d) => $d ? $d->locale('id')->translatedFormat('d F Y') : '';
    $nikChars = str_split(str_pad((string) $p->nik, 16, ' '));
    $nikGroups = [array_slice($nikChars, 0, 6), array_slice($nikChars, 6, 6), array_slice($nikChars, 12, 4)];
    $noInduk = explode('-', (string) $p->no_induk);
    if (count($noInduk) !== 3) { $noInduk = [(string) $p->no_induk, '', '']; }
    $tglDaftar = $p->created_at ? $p->created_at->locale('id')->translatedFormat('d F Y') : '';
@endphp

{{-- ===== Toolbar ===== --}}
<div class="toolbar no-print">
    <div class="toolbar-title">
        Pratinjau Formulir Cetak
        <small>{{ $p->nama_lengkap }} • Ukuran kertas A4</small>
    </div>
    <div class="toolbar-actions">
        @php
            $backUrl = route('pendaftaran.show', $p->id);
            if (request('from') === 'siswa' || url()->previous() === route('siswa')) {
                $backUrl = route('siswa');
            } elseif (url()->previous() && url()->previous() !== url()->current()) {
                $backUrl = url()->previous();
            }
        @endphp
        <a href="{{ $backUrl }}" class="tb-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Kembali
        </a>
        <button type="button" class="tb-btn primary" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak
        </button>
        <button type="button" class="tb-btn success" id="btnPdf" onclick="unduhPdf()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span id="btnPdfText">Unduh PDF</span>
        </button>
    </div>
</div>

<div class="sheet-wrap">
<div class="paper" id="paper">

    {{-- ===== KOP SURAT ===== --}}
    <div class="kop">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="kop-logo">
        <div class="kop-text">
            <div class="nama">YAYASAN PONDOK PESANTREN<br>MUTTAQIN JOSENAN MADIUN</div>
            <div class="alamat">Jln. Nogososro No. 26 Josenan Taman Kota Madiun Jawa Timur Kode Pos : 63134</div>
            <div class="alamat">Telp. +6285785028124 Email : <span class="email">ponpesalmuttaqin001&#64;gmail.com</span></div>
        </div>
    </div>
    <div class="garis-tebal"></div>
    <div class="garis-tipis"></div>

    <div class="judul">FORMULIR PENDAFTARAN SANTRI BARU</div>

    <div class="no-induk-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2mm;">
        <div style="display:flex; align-items:center; gap:2.5mm;">
            <span style="font-weight:700;">Jenjang :</span>
            <span><span class="box">{!! $ck(($p->jenjang ?? '') === 'SMP') !!}</span>SMP</span>
            <span><span class="box">{!! $ck(($p->jenjang ?? '') === 'SMA') !!}</span>SMA</span>
            <span><span class="box">{!! $ck(($p->jenjang ?? 'Reguler') === 'Reguler') !!}</span>Reguler</span>
            @if($p->nisn)
                <span class="gap"></span>
                <span style="font-weight:700;">NISN :</span>
                <span style="font-family:monospace; font-weight:700;">{{ $p->nisn }}</span>
            @endif
        </div>
        <div style="display:flex; align-items:center; gap:1.5mm;">
            <span>No. Induk :</span>
            <div class="no-induk-box">
                <span>{{ $noInduk[0] }}</span><span>{{ $noInduk[1] }}</span><span>{{ $noInduk[2] }}</span>
            </div>
            <span class="no-induk-note">(Diisi petugas)</span>
        </div>
    </div>

    {{-- ===== 1. IDENTITAS SANTRI ===== --}}
    <div class="sec">1.&nbsp; IDENTITAS SANTRI</div>
    <div class="ind">
        <div class="r"><span class="n">1.</span><span class="l">Nama Lengkap</span><span class="c">:</span><span class="v">{{ $p->nama_lengkap }}</span></div>
        <div class="r"><span class="n">2.</span><span class="l">Nama Panggilan</span><span class="c">:</span><span class="v">{{ $p->nama_panggilan }}</span></div>

        <div class="r">
            <span class="n">3.</span><span class="l">No. KTP (NIK)</span><span class="c">:</span>
            <div class="nik">
                @foreach ($nikGroups as $gi => $group)
                    @if ($gi > 0)<span class="dash">-</span>@endif
                    <div class="grp">
                        @foreach ($group as $ch)<span class="cell">{{ trim($ch) }}</span>@endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div class="r">
            <span class="n">4.</span><span class="l">Tempat, Tanggal Lahir</span><span class="c">:</span>
            <span class="v">{{ $p->tempat_lahir }}</span><span class="t">,&nbsp;</span><span class="v">{{ $tgl($p->tanggal_lahir) }}</span>
        </div>

        <div class="r">
            <span class="n">5.</span><span class="l">Jenis Kelamin</span><span class="c">:</span>
            <span class="t"><span class="box">{!! $ck($p->jenis_kelamin === 'Laki-laki') !!}</span>Laki-laki</span>
            <span class="gap"></span>
            <span class="t"><span class="box">{!! $ck($p->jenis_kelamin === 'Perempuan') !!}</span>Perempuan,</span>
        </div>

        <div class="r">
            <span class="n">6.</span><span class="l">Anak Ke</span><span class="c">:</span>
            <span class="v">{{ $p->anak_ke }}</span><span class="gap"></span>
            <span class="t">Jumlah Saudara Kandung</span><span class="c">:</span>
            <span class="v w-sm">{{ $p->jumlah_saudara }}</span>
        </div>

        <div class="r">
            <span class="n">7.</span><span class="l">Tinggal Bersama</span><span class="c">:</span>
            <span class="v">{{ $p->tinggal_bersama }}</span><span class="gap"></span>
            <span class="t">Mulai Mengikuti Pengajian</span><span class="c">:</span>
            <span class="v w-sm">{{ $p->mulai_pengajian }}</span>
        </div>

        {{-- 8. Alamat lengkap --}}
        <div class="r">
            <span class="n">8.</span><span class="l">Alamat Lengkap</span>
            <span class="t" style="width:17mm">Jl/Kp/Dsn</span><span class="c">:</span><span class="v">{{ $p->alamat_jalan }}</span>
            <span class="gap"></span><span class="t" style="width:20mm">RT/RW</span><span class="c">:</span><span class="v w-sm" style="flex-basis:36mm">{{ $p->rt_rw }}</span>
        </div>
        <div class="r">
            <span class="sp"></span>
            <span class="t" style="width:17mm">Desa/Kel</span><span class="c">:</span><span class="v">{{ $p->desa_kelurahan }}</span>
            <span class="gap"></span><span class="t" style="width:20mm">Kec</span><span class="c">:</span><span class="v w-sm" style="flex-basis:36mm">{{ $p->kecamatan }}</span>
        </div>
        <div class="r">
            <span class="sp"></span>
            <span class="t" style="width:17mm">Kab/Kota</span><span class="c">:</span><span class="v">{{ $p->kab_kota }}</span>
            <span class="gap"></span><span class="t" style="width:20mm">Kode Pos</span><span class="c">:</span><span class="v w-sm" style="flex-basis:36mm">{{ $p->kode_pos }}</span>
        </div>
        <div class="r">
            <span class="sp"></span>
            <span class="t" style="width:17mm">Provinsi</span><span class="c">:</span><span class="v">{{ $p->provinsi }}</span>
            <span class="gap"></span><span class="t" style="width:20mm">Warga Negara</span><span class="c">:</span><span class="v w-sm" style="flex-basis:36mm">{{ $p->warga_negara }}</span>
        </div>

        {{-- 9. Alamat pengajian --}}
        <div class="r">
            <span class="n">9.</span><span class="l">Alamat Pengajian</span>
            <span class="t" style="width:17mm">Kelompok</span><span class="c">:</span><span class="v">{{ $p->kelompok }}</span>
            <span class="gap"></span><span class="t" style="width:20mm">Desa</span><span class="c">:</span><span class="v w-sm" style="flex-basis:36mm">{{ $p->desa }}</span>
        </div>
        <div class="r">
            <span class="sp"></span>
            <span class="t" style="width:17mm">Daerah</span><span class="c">:</span><span class="v">{{ $p->daerah }}</span>
        </div>

        {{-- 10. Pendidikan terakhir & Asal Sekolah --}}
        <div class="r">
            <span class="n">10.</span><span class="l">Pendidikan Terakhir</span><span class="c">:</span>
            <span class="v" style="flex:0 0 24mm">{{ $p->pendidikan_terakhir }}</span><span class="gap"></span>
            <span class="t">Asal Sekolah</span><span class="c">:</span><span class="v">{{ $p->nama_sekolah_asal ?? '-' }}</span><span class="gap"></span>
            <span class="t">Tahun Lulus</span><span class="c">:</span><span class="v" style="flex:0 0 16mm">{{ $p->tahun_lulus }}</span>
        </div>

        <div class="r">
            <span class="n">11.</span><span class="l">Status Mondok</span><span class="c">:</span>
            <span class="t"><span class="box">{!! $ck($p->status_mondok === 'Person') !!}</span>Person</span>
            <span class="gap"></span>
            <span class="t"><span class="box">{!! $ck($p->status_mondok === 'Utusan/Kiriman') !!}</span>Utusan/Kiriman :</span>
            <span class="v"></span>
        </div>

        <div class="r">
            <span class="n">12.</span><span class="l">Penanggung Jawab Biaya</span><span class="c">:</span>
            <span class="v">{{ $p->pj_biaya_nama }}</span><span class="gap"></span>
            <span class="t">No Tlp/Hp</span><span class="c">:</span><span class="v w-sm" style="flex-basis:42mm">{{ $p->pj_biaya_hp }}</span>
        </div>
        <div class="r">
            <span class="sp"></span>
            <span class="t">&bull; Bantuan Sosial (KIP / PIP / PKH) :&nbsp;</span>
            <span class="t"><span class="box">{!! $ck($p->penerima_kip) !!}</span>Ya</span>
            <span class="gap" style="width:2mm"></span>
            <span class="t"><span class="box">{!! $ck(! $p->penerima_kip) !!}</span>Tidak</span>
            @if($p->penerima_kip)
                <span class="gap" style="width:4mm"></span>
                <span class="t">No. KIP/KKS :&nbsp;</span>
                <span class="v" style="font-family:monospace; font-weight:700;">{{ $p->nomor_kip }}</span>
            @endif
        </div>

        <div class="r">
            <span class="n">13.</span><span class="l">Riwayat Kesehatan</span><span class="c">:</span>
            <span class="t">Penyakit yang pernah diderita :</span><span class="v">{{ $p->penyakit_diderita }}</span>
        </div>
        <div class="r">
            <span class="sp"></span>
            <span class="t">&bull; Gol Darah :&nbsp;</span><span class="v" style="flex:0 0 17mm">{{ $p->gol_darah }}</span>
            <span class="gap"></span>
            <span class="t">&bull; Tinggi :&nbsp;</span><span class="v" style="flex:0 0 13mm">{{ $p->tinggi_badan }}</span><span class="t">&nbsp;cm</span>
            <span class="gap"></span>
            <span class="t">&bull; Berat :&nbsp;</span><span class="v" style="flex:0 0 13mm">{{ $p->berat_badan }}</span><span class="t">&nbsp;kg</span>
            <span class="gap"></span>
            <span class="t">&bull; Alergi :&nbsp;</span><span class="v">{{ $p->alergi }}</span>
        </div>

        <div class="r">
            <span class="n">14.</span><span class="l">Keahlian/Prestasi</span><span class="c">:</span>
            <span class="v">{{ $p->keahlian_prestasi }}</span>
        </div>
        <div class="r"><span class="sp"></span><span class="v"></span></div>
    </div>

    {{-- ===== 2. ORANG TUA SANTRI ===== --}}
    <div class="sec">2.&nbsp; ORANG TUA SANTRI</div>
    <div class="ortu ind" style="padding-left:6mm">
        @foreach (['ayah' => 'Bapak', 'ibu' => 'Ibu'] as $k => $label)
            @php $status = $p->{$k.'_status'}; $wn = $p->{$k.'_wn'}; $wnIndo = strtolower(trim((string) $wn)) === 'indonesia'; @endphp
            <div>
                <div class="r"><span class="n">1.</span><span class="l">Nama {{ $label }}</span><span class="c">:</span><span class="v">{{ $p->{$k.'_nama'} }}</span></div>
                <div class="r" style="justify-content:flex-end;min-height:3.6mm">
                    <span class="{{ $status === 'Hidup' ? 'sel' : 'strike' }}">Hidup</span>/<span class="{{ $status === 'Meninggal' ? 'sel' : 'strike' }}">Meninggal</span>
                </div>
                <div class="r"><span class="n">2.</span><span class="l">Tempat, Tanggal Lahir</span><span class="c">:</span><span class="v">{{ $p->{$k.'_ttl'} }}</span></div>
                <div class="r"><span class="n">3.</span><span class="l">Pendidikan Terakhir</span><span class="c">:</span><span class="v">{{ $p->{$k.'_pendidikan'} }}</span></div>
                <div class="r"><span class="n">4.</span><span class="l">Pekerjaan, Penghasilan</span><span class="c">:</span><span class="v">{{ collect([$p->{$k.'_pekerjaan'}, $p->{$k.'_penghasilan'}])->filter()->implode(', ') }}</span></div>
                <div class="r"><span class="n">5.</span><span class="l">Agama</span><span class="c">:</span><span class="v">{{ $p->{$k.'_agama'} }}</span></div>
                <div class="r"><span class="n">6.</span><span class="l">Alamat Lengkap</span><span class="c">:</span><span class="v">{{ $p->{$k.'_alamat'} }}</span></div>
                <div class="r"><span class="v"></span></div>
                <div class="r"><span class="v"></span></div>
                <div class="r">
                    <span class="t">Warga Negara :&nbsp;</span>
                    <span class="t"><span class="box">{!! $ck($wnIndo) !!}</span>Indonesia</span>
                    <span class="gap" style="width:2mm"></span>
                    <span class="t"><span class="box">{!! $ck(! $wnIndo && $wn) !!}</span>Asing:</span>
                    <span class="v">{{ $wnIndo ? '' : $wn }}</span>
                </div>
                <div class="r"><span class="t">&bull;&nbsp; No. Tlp/HP</span><span class="c" style="margin-left:auto">:</span><span class="v" style="flex:0 0 46mm">{{ $p->{$k.'_hp'} }}</span></div>
                <div class="r"><span class="n">7.</span><span class="t">Alamat Pengajian</span></div>
                <div class="r"><span class="sp"></span><span class="t" style="width:20mm">&bull; Daerah</span><span class="c">:</span><span class="v">{{ $p->{$k.'_daerah'} }}</span></div>
                <div class="r"><span class="sp"></span><span class="t" style="width:20mm">&bull; Desa</span><span class="c">:</span><span class="v">{{ $p->{$k.'_desa'} }}</span></div>
                <div class="r"><span class="sp"></span><span class="t" style="width:20mm">&bull; Kelompok</span><span class="c">:</span><span class="v">{{ $p->{$k.'_kelompok'} }}</span></div>
            </div>
        @endforeach
    </div>

    {{-- ===== 3. PERSYARATAN ===== --}}
    <div class="sec">3.&nbsp; PERSYARATAN <span style="font-weight:700">(yang harus dipenuhi)</span></div>
    <div class="syarat ind" style="padding-left:6mm">
        <div>
            <div class="r">
                <span class="n" style="width:4.5mm">1.</span><span class="lbl">Foto Copy Akta Kelahiran</span>
                <span class="opts"><span class="box">{!! $ck($p->berkas_akta) !!}</span>Sudah&nbsp;&nbsp;<span class="box">{!! $ck(! $p->berkas_akta) !!}</span>Belum</span>
            </div>
            <div class="r">
                <span class="n" style="width:4.5mm">2.</span><span class="lbl">Foto Copy Kartu Keluarga</span>
                <span class="opts"><span class="box">{!! $ck($p->berkas_kk) !!}</span>Sudah&nbsp;&nbsp;<span class="box">{!! $ck(! $p->berkas_kk) !!}</span>Belum</span>
            </div>
        </div>
        <div>
            <div class="r">
                <span class="n" style="width:4.5mm">3.</span><span class="lbl">Foto Copy KTP Ayah &amp; Ibu</span>
                <span class="opts"><span class="box">{!! $ck($p->berkas_ktp_ortu) !!}</span>Sudah&nbsp;&nbsp;<span class="box">{!! $ck(! $p->berkas_ktp_ortu) !!}</span>Belum</span>
            </div>
            <div class="r">
                <span class="n" style="width:4.5mm">4.</span><span class="lbl">Surat Sanggup Mentaati<br>Peraturan Pondok</span>
                <span class="opts"><span class="box">{!! $ck($p->berkas_surat_pernyataan) !!}</span>Sudah&nbsp;&nbsp;<span class="box">{!! $ck(! $p->berkas_surat_pernyataan) !!}</span>Belum</span>
            </div>
        </div>
    </div>

    {{-- ===== TANDA TANGAN ===== --}}
    <div class="ttd">
        <div>
            <div>&nbsp;</div>
            <div>Petugas Pendaftaran</div>
            <div class="ruang"></div>
            <div class="kurung">(.......................................)</div>
        </div>
        <div>
            <div>&nbsp;</div>
            <div>Orang Tua/Wali Santri</div>
            <div class="ruang"></div>
            <div class="kurung">(.......................................)</div>
        </div>
        <div>
            <div>Madiun, {{ $tglDaftar }}</div>
            <div>Calon Santri</div>
            <div class="ruang"></div>
            <div class="kurung">(.......................................)</div>
        </div>
    </div>

</div>
</div>

<script src="{{ asset('js/vendor/html2pdf.bundle.min.js') }}"></script>
<script>
    function unduhPdf() {
        const btnText = document.getElementById('btnPdfText');
        const btn = document.getElementById('btnPdf');
        const el = document.getElementById('paper');
        btn.disabled = true;
        btnText.textContent = 'Menyiapkan PDF...';

        // Hilangkan bayangan/margin sementara agar hasil PDF presisi A4
        const oldShadow = el.style.boxShadow, oldMargin = el.style.margin;
        el.style.boxShadow = 'none';
        el.style.margin = '0';
        window.scrollTo(0, 0);

        html2pdf().set({
            margin: 0,
            filename: 'Formulir-Pendaftaran-{{ \Illuminate\Support\Str::slug($p->nama_lengkap) }}.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff' },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['avoid-all'] }
        }).from(el).save().finally(() => {
            el.style.boxShadow = oldShadow;
            el.style.margin = oldMargin;
            btn.disabled = false;
            btnText.textContent = 'Unduh PDF';
        });
    }

    // Buka dialog cetak otomatis jika diakses dengan ?print=1
    if (new URLSearchParams(location.search).get('print') === '1') {
        window.addEventListener('load', () => setTimeout(() => window.print(), 400));
    }
</script>
</body>
</html>
