@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
@php
    $authUser = auth()->user();
    $tz = config('app.timezone') ?: 'Asia/Jakarta';
    $nowInTz = now()->timezone($tz);
    $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][(int)$nowInTz->dayOfWeek];
    $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][(int)$nowInTz->format('n')];
    $tanggalFormatted = $hariIndo . ', ' . $nowInTz->format('j') . ' ' . $bulanIndo . ' ' . $nowInTz->format('Y');
    $firstName = explode(' ', trim($authUser->name ?? 'Pengguna'))[0];
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- Hero Welcome Card untuk Mobile & Desktop --}}
    <div class="beranda-hero-card">
        <div class="beranda-hero-content" style="width: 100%;">
            <div class="beranda-hero-header">
                <h1 class="beranda-hero-title">
                    Halo, {{ $firstName }} 👋
                </h1>
                <span class="beranda-hero-date">{{ $tanggalFormatted }}</span>
            </div>

            <div class="beranda-ticker-wrapper" id="berandaTickerWrapper">
                @if(!empty($pengumumanList) && count($pengumumanList) > 0)
                    <div class="beranda-ticker-carousel" id="berandaTickerCarousel">
                        @foreach($pengumumanList as $idx => $item)
                            <div class="beranda-ticker-item {{ $idx === 0 ? 'active' : '' }}" data-index="{{ $idx }}">
                                <div class="ticker-badge-col">
                                    <span class="ticker-badge ticker-badge-{{ $item['tipe'] }}">
                                        @if($item['tipe'] === 'jadwal')
                                            <svg class="ticker-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                        @else
                                            <svg class="ticker-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        @endif
                                        {{ $item['badge'] }}
                                    </span>
                                </div>
                                <div class="ticker-text-col">
                                    @if(!empty($item['url']))
                                        <a href="{{ $item['url'] }}" class="ticker-text ticker-link" title="Buka jadwal">
                                            <span>{{ $item['text'] }}</span>
                                            <svg class="ticker-link-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                                        </a>
                                    @else
                                        <span class="ticker-text">{{ $item['text'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if(count($pengumumanList) > 1)
                        <div class="ticker-controls">
                            <span class="ticker-counter" id="tickerCounter">1/{{ count($pengumumanList) }}</span>
                            <button type="button" class="ticker-btn" id="tickerPrev" title="Sebelumnya" aria-label="Sebelumnya">‹</button>
                            <button type="button" class="ticker-btn" id="tickerNext" title="Selanjutnya" aria-label="Selanjutnya">›</button>
                        </div>
                    @endif
                @else
                    <div class="beranda-ticker-empty">
                        <svg class="ticker-empty-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span class="ticker-empty-text">tidak ada jadwal mengajar / tidak ada agenda hari ini</span>
                        @if(Route::has('jadwal-pelajaran.saya'))
                            <a href="{{ route('jadwal-pelajaran.saya') }}" class="ticker-empty-link">
                                <span>Lihat Jadwal Saya</span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Kartu Statistik Utama (2x2 Grid Rapi di Mobile, 4 Kolom di Desktop) --}}
    <div class="stats-grid">
        <!-- Siswa Keseluruhan -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">
                    {{ $activeRoom === 'smp' ? 'Siswa SMP' : ($activeRoom === 'sma' ? 'Siswa SMA' : 'Santri Keseluruhan') }}
                </span>
                <div class="stat-icon-wrapper stat-icon-total">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($statistik['total_siswa'], 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Siswa Aktif -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Aktif di Asrama</span>
                <div class="stat-icon-wrapper stat-icon-aktif">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($statistik['siswa_aktif'], 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Siswa Pulang -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Izin Pulang</span>
                <div class="stat-icon-wrapper stat-icon-pulang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($statistik['siswa_pulang'], 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Siswa Sakit -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Izin Sakit</span>
                <div class="stat-icon-wrapper stat-icon-sakit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($statistik['siswa_sakit'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

</div>

@if(!empty($pengumumanList) && count($pengumumanList) > 1)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const items = document.querySelectorAll('.beranda-ticker-item');
    if (items.length <= 1) return;

    let currentIndex = 0;
    const total = items.length;
    const counter = document.getElementById('tickerCounter');
    let timer = null;

    function showItem(index) {
        items.forEach((item, idx) => {
            item.classList.toggle('active', idx === index);
        });
        if (counter) {
            counter.textContent = (index + 1) + '/' + total;
        }
        currentIndex = index;
    }

    function nextItem() {
        showItem((currentIndex + 1) % total);
    }

    function prevItem() {
        showItem((currentIndex - 1 + total) % total);
    }

    function startTimer() {
        if (!timer) {
            timer = setInterval(nextItem, 4000);
        }
    }

    function stopTimer() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    const prevBtn = document.getElementById('tickerPrev');
    const nextBtn = document.getElementById('tickerNext');
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            prevItem();
            stopTimer();
            startTimer();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            nextItem();
            stopTimer();
            startTimer();
        });
    }

    const wrapper = document.getElementById('berandaTickerWrapper');
    if (wrapper) {
        wrapper.addEventListener('mouseenter', stopTimer);
        wrapper.addEventListener('mouseleave', startTimer);
    }

    startTimer();
});
</script>
@endif
@endsection


