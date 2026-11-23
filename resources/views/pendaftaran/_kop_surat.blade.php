{{-- ===== KOP SURAT RESMI LEMBAGA ===== --}}
<div class="kop">
    <img src="{{ $kop['logo_url'] }}" alt="Logo" class="kop-logo">
    <div class="kop-text">
        @if(!empty($kop['yayasan_nama']))
            <div class="yayasan">{{ $kop['yayasan_nama'] }}</div>
        @endif
        <div class="nama">
            @foreach(($kop['nama_lines'] ?? [$kop['nama']]) as $idx => $line)
                @if($idx > 0)<br>@endif{{ $line }}
            @endforeach
        </div>
        @if(!empty($kop['alamat']))
            <div class="alamat">{{ $kop['alamat'] }}</div>
        @endif
        @if(!empty($kop['telepon']) || !empty($kop['email']))
            <div class="alamat kop-kontak">
                @if(!empty($kop['telepon']))Telp. {{ $kop['telepon'] }}@endif
                @if(!empty($kop['telepon']) && !empty($kop['email'])) &nbsp; @endif
                @if(!empty($kop['email']))Email : <span class="email">{{ $kop['email'] }}</span>@endif
            </div>
        @endif
    </div>
</div>
<div class="garis-tipis"></div>
<div class="garis-tebal"></div>
