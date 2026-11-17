@extends('layouts.app')

@section('title', 'Rekap Nilai & Analisis Ujian')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px;">
    <a href="{{ route('ujian.soal') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; font-size:13px; padding:7px 14px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>+ Buat Ujian Baru</span>
    </a>
</div>
@endsection

@section('content')
<div style="max-width:800px; margin:40px auto; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:50px 24px;">
    <div style="width:64px; height:64px; border-radius:50%; background:#eff6ff; color:#0284c7; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        </svg>
    </div>
    <h3 style="font-size:18px; font-weight:800; color:#1e293b; margin:0 0 8px;">Belum Ada Data Ujian</h3>
    <p style="font-size:13px; color:#64748b; margin:0 auto 20px; max-width:440px;">
        Belum ada ujian yang dibuat atau dikoreksi. Silakan unggah soal ujian dan cetak lembar LJK untuk memulai pelaksanaan ujian.
    </p>
    <a href="{{ route('ujian.soal') }}" class="btn btn-primary" style="font-size:13px; padding:9px 20px; border-radius:8px; display:inline-inline-flex; align-items:center; gap:6px;">
        Buka Menu Soal Ujian
    </a>
</div>
@endsection

