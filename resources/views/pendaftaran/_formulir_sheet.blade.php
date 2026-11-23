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

<div class="paper" @if(!empty($paperId)) id="{{ $paperId }}" @endif>
    @include('pendaftaran._kop_surat', ['kop' => $kop])

    <div class="judul">FORMULIR PENDAFTARAN SANTRI BARU</div>

    <div class="no-induk-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2mm;">
        <div style="display:flex; align-items:center; gap:2mm;">
            @if($p->nisn)
                <span style="font-weight:700;">NISN :</span>
                <span style="font-family:monospace; font-weight:700;">{{ $p->nisn }}</span>
            @endif
        </div>
        <div style="display:flex; align-items:center; gap:1.5mm;">
            <span>No. Registrasi :</span>
            <div class="no-induk-box">
                <span>{{ $noInduk[0] }}</span><span>{{ $noInduk[1] }}</span><span>{{ $noInduk[2] }}</span>
            </div>
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
            <span class="t"><span class="box">{!! $ck($p->jenis_kelamin === 'Perempuan') !!}</span>Perempuan</span>
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

        <div class="r">
            <span class="n">9.</span><span class="l">Alamat Pengajian</span>
            <span class="t" style="width:17mm">Kelompok</span><span class="c">:</span><span class="v">{{ $p->kelompok }}</span>
            <span class="gap"></span><span class="t" style="width:20mm">Desa</span><span class="c">:</span><span class="v w-sm" style="flex-basis:36mm">{{ $p->desa }}</span>
        </div>
        <div class="r">
            <span class="sp"></span>
            <span class="t" style="width:17mm">Daerah</span><span class="c">:</span><span class="v">{{ $p->daerah }}</span>
        </div>

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
