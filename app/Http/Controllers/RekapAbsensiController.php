<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\Presensi;
use App\Models\PresensiGuru;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RekapAbsensiController extends Controller
{
    /**
     * Submenu: Rekap Absensi Guru.
     */
    public function guru(Request $request)
    {
        $user = auth()->user();
        $lembagas = $user->accessibleLembagas();

        // Filter Lembaga (Dukung "Semua Lembaga" jika lembaga_id dikirim kosong/all)
        $isSemuaLembaga = $request->exists('lembaga_id') && (
            $request->get('lembaga_id') === '' || 
            $request->get('lembaga_id') === 'all' || 
            is_null($request->get('lembaga_id'))
        );

        if ($isSemuaLembaga) {
            $lembagaId = null;
        } elseif ($request->filled('lembaga_id')) {
            $lembagaId = (int) $request->get('lembaga_id');
        } else {
            $currentLembaga = $user->currentLembaga() ?: $lembagas->first();
            $lembagaId = $currentLembaga ? $currentLembaga->id : null;
        }

        // Filter Periode
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $tanggal = $request->get('tanggal');
        $status = $request->get('status');
        $search = $request->get('search');

        // Sinkronisasi otomatis data tap RFID multi-lokasi ke presensi guru
        $this->syncRfidTapsToPresensiGuru($tanggal, $bulan, $tahun);

        // Query Presensi Terpadu
        $query = $this->buildPresensiQuery($lembagaId, $tanggal, $bulan, $tahun, $status, $search);

        // Summary Statistik (sebelum paginate)
        $summaryQuery = clone $query;
        $ringkasan = [
            'total'     => (clone $summaryQuery)->count(),
            'hadir'     => (clone $summaryQuery)->where('status', 'hadir')->count(),
            'terlambat' => (clone $summaryQuery)->where('status', 'terlambat')->count(),
            'izin'      => (clone $summaryQuery)->where('status', 'izin')->count(),
            'sakit'     => (clone $summaryQuery)->where('status', 'sakit')->count(),
            'alpa'      => (clone $summaryQuery)->where('status', 'alpa')->count(),
        ];

        $perPage = (int) $request->get('per_page', 15);
        if ($perPage < 5 || $perPage > 200) {
            $perPage = 15;
        }

        $presensis = $query->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('rekap-absensi.guru', compact(
            'presensis',
            'lembagas',
            'lembagaId',
            'bulan',
            'tahun',
            'tanggal',
            'status',
            'search',
            'ringkasan'
        ));
    }

    /**
     * Koreksi manual data presensi oleh admin.
     */
    public function koreksi(Request $request, $id)
    {
        $validated = $request->validate([
            'status'     => 'required|in:hadir,terlambat,izin,sakit,alpa',
            'jam_masuk'  => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $presensi = PresensiGuru::findOrFail($id);

        $presensi->update([
            'status'     => $validated['status'],
            'jam_masuk'  => $validated['jam_masuk'] ? $validated['jam_masuk'] . ':00' : $presensi->jam_masuk,
            'jam_pulang' => $validated['jam_pulang'] ? $validated['jam_pulang'] . ':00' : $presensi->jam_pulang,
            'keterangan' => $validated['keterangan'] ? ($presensi->keterangan ? $presensi->keterangan . ' | Koreksi: ' . $validated['keterangan'] : 'Koreksi: ' . $validated['keterangan']) : $presensi->keterangan,
        ]);

        return redirect()->back()->with('success', 'Data presensi berhasil dikoreksi.');
    }

    /**
     * Ekspor Rekap Absensi Guru ke CSV/Excel.
     */
    public function export(Request $request)
    {
        $isSemuaLembaga = $request->exists('lembaga_id') && (
            $request->get('lembaga_id') === '' || 
            $request->get('lembaga_id') === 'all' || 
            is_null($request->get('lembaga_id'))
        );

        if ($isSemuaLembaga) {
            $lembagaId = null;
        } elseif ($request->filled('lembaga_id')) {
            $lembagaId = (int) $request->get('lembaga_id');
        } else {
            $user = auth()->user();
            $lembagaId = $user->currentLembaga()?->id ?: $user->accessibleLembagas()->first()?->id;
        }

        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $tanggal = $request->get('tanggal');

        $this->syncRfidTapsToPresensiGuru($tanggal, $bulan, $tahun);

        $query = $this->buildPresensiQuery($lembagaId, $tanggal, $bulan, $tahun);
        $records = $query->orderBy('tanggal', 'asc')->orderBy('jam_masuk', 'asc')->get();

        $fileName = 'rekap_absensi_guru_' . $tahun . '_' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header kolom
            fputcsv($handle, ['No', 'Tanggal', 'NIP', 'Nama Guru', 'Lembaga', 'Jam Masuk', 'Jam Pulang', 'Status', 'Jarak Masuk (m)', 'Keterangan']);

            $no = 1;
            foreach ($records as $row) {
                fputcsv($handle, [
                    $no++,
                    $row->tanggal ? $row->tanggal->format('d/m/Y') : '-',
                    $row->user->nip ?? '-',
                    $row->user->name ?? '-',
                    $row->lembaga->nama ?? '-',
                    $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-',
                    $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-',
                    $row->status_label,
                    $row->jarak_masuk_meter ?? '-',
                    $row->keterangan ?? '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Cetak Lembar Rekap Absensi Guru (Format A4 PDF/Print Friendly).
     */
    public function cetak(Request $request)
    {
        $isSemuaLembaga = $request->exists('lembaga_id') && (
            $request->get('lembaga_id') === '' || 
            $request->get('lembaga_id') === 'all' || 
            is_null($request->get('lembaga_id'))
        );

        if ($isSemuaLembaga) {
            $lembagaId = null;
        } elseif ($request->filled('lembaga_id')) {
            $lembagaId = (int) $request->get('lembaga_id');
        } else {
            $user = auth()->user();
            $lembagaId = $user->currentLembaga()?->id ?: $user->accessibleLembagas()->first()?->id;
        }

        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $tanggal = $request->get('tanggal');

        $this->syncRfidTapsToPresensiGuru($tanggal, $bulan, $tahun);

        $lembaga = $lembagaId ? Lembaga::find($lembagaId) : (auth()->user()->currentLembaga() ?: Lembaga::first());

        $query = $this->buildPresensiQuery($lembagaId, $tanggal, $bulan, $tahun);
        $records = $query->orderBy('tanggal', 'asc')->orderBy('jam_masuk', 'asc')->get();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$bulan] ?? '';

        return view('rekap-absensi.cetak', compact('records', 'lembaga', 'bulan', 'tahun', 'namaBulan'));
    }

    /**
     * Query builder presensi guru terpadu yang fleksibel terhadap multi-lembaga,
     * memastikan guru yang berafiliasi dengan lembaga sekolah (misal SMP / SMA) tetap tampil
     * meskipun akun guru terdaftar secara sentral di Yayasan.
     */
    protected function buildPresensiQuery(?int $lembagaId, ?string $tanggal, int $bulan, int $tahun, ?string $status = null, ?string $search = null)
    {
        $query = PresensiGuru::with(['user', 'lembaga']);

        if ($lembagaId) {
            $selectedLembaga = Lembaga::find($lembagaId);
            $kodeLembaga = strtolower($selectedLembaga?->kode ?? '');
            $isYayasan = $kodeLembaga === 'yayasan' || str_contains(strtolower($selectedLembaga?->nama ?? ''), 'yayasan');

            // Jika lembaga yang dipilih BUKAN Yayasan (misal SMP atau SMA):
            // Batasi query ke guru yang berkaitan dengan unit/sekolah tersebut.
            // Jika Yayasan yang dipilih, Yayasan adalah induk seluruh lembaga sehingga seluruh guru ditampilkan.
            if (!$isYayasan) {
                $query->where(function ($q) use ($lembagaId, $selectedLembaga) {
                    $q->where('lembaga_id', $lembagaId)
                      ->orWhereHas('user', function ($uq) use ($lembagaId, $selectedLembaga) {
                          $uq->where('primary_lembaga_id', $lembagaId)
                             ->orWhereHas('lembagas', fn($lq) => $lq->where('lembagas.id', $lembagaId));

                          if ($selectedLembaga) {
                              $singkatan = $selectedLembaga->singkatan ?? $selectedLembaga->kode;
                              $uq->orWhere('jabatan', 'like', "%{$singkatan}%")
                                 ->orWhere('jabatan', 'like', "%{$selectedLembaga->kode}%");
                          }
                      });
                });
            }
        }

        if ($tanggal) {
            $query->whereDate('tanggal', $tanggal);
        } else {
            $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
        }

        if ($status && in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Sinkronisasi data tap RFID guru/pegawai dari tabel presensis ke presensi_gurus.
     * Memastikan tap yang tercatat di dashboard RFID multi-lokasi otomatis masuk ke rekap absensi guru.
     */
    protected function syncRfidTapsToPresensiGuru(?string $tanggal = null, ?int $bulan = null, ?int $tahun = null): void
    {
        if (!Schema::hasTable('presensis') || !Schema::hasTable('presensi_gurus')) {
            return;
        }

        $tapsQuery = Presensi::where('tipe_entitas', 'guru_pegawai');

        if ($tanggal) {
            $tapsQuery->whereDate('waktu_tap', $tanggal);
        } elseif ($bulan && $tahun) {
            $tapsQuery->whereMonth('waktu_tap', $bulan)->whereYear('waktu_tap', $tahun);
        } else {
            $tapsQuery->whereDate('waktu_tap', Carbon::today());
        }

        $taps = $tapsQuery->orderBy('waktu_tap', 'asc')->get();

        if ($taps->isEmpty()) {
            return;
        }

        // Kelompokkan berdasarkan entitas_id (user_id) dan tanggal tap
        $grouped = $taps->groupBy(function ($tap) {
            $date = Carbon::parse($tap->waktu_tap)->toDateString();
            return "{$tap->entitas_id}_{$date}";
        });

        foreach ($grouped as $key => $tapList) {
            $firstTap = $tapList->first();
            $userId = (int) $firstTap->entitas_id;
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $tapDate = Carbon::parse($firstTap->waktu_tap)->toDateString();
            $jamMasuk = Carbon::parse($firstTap->waktu_tap)->format('H:i:s');

            // Tentukan status masuk dari status_kehadiran log tap
            $statusMasuk = match ($firstTap->status_kehadiran) {
                'terlambat' => 'terlambat',
                default     => 'hadir',
            };

            // Tentukan lembaga_id target
            $targetLembagaId = $user->primary_lembaga_id;
            if (!$targetLembagaId) {
                $targetLembagaId = $user->lembagas()->first()?->id ?: 1;
            }

            // Jika serial device tap mengindikasikan lembaga spesifik (misal 'kantor smp')
            $devId = strtolower((string) $firstTap->device_id);
            if (str_contains($devId, 'smp')) {
                $smp = Lembaga::where(fn($q) => $q->where('kode', 'smp')->orWhere('singkatan', 'SMP'))->first();
                if ($smp) {
                    $targetLembagaId = $smp->id;
                }
            } elseif (str_contains($devId, 'sma')) {
                $sma = Lembaga::where(fn($q) => $q->where('kode', 'sma')->orWhere('singkatan', 'SMA'))->first();
                if ($sma) {
                    $targetLembagaId = $sma->id;
                }
            }

            // Cek apakah presensi guru untuk user ini di tanggal ini sudah ada
            $presensi = PresensiGuru::where('user_id', $userId)
                ->whereDate('tanggal', $tapDate)
                ->first();

            if (!$presensi) {
                // Jam pulang jika ada tap berikutnya yang berselisih waktu >= 3 menit
                $jamPulang = null;
                if ($tapList->count() > 1) {
                    $lastTap = $tapList->last();
                    $diffSec = Carbon::parse($lastTap->waktu_tap)->diffInSeconds(Carbon::parse($firstTap->waktu_tap));
                    if ($diffSec >= 180) { // Debounce 3 menit
                        $jamPulang = Carbon::parse($lastTap->waktu_tap)->format('H:i:s');
                    }
                }

                PresensiGuru::create([
                    'user_id'       => $userId,
                    'lembaga_id'    => $targetLembagaId,
                    'tanggal'       => $tapDate,
                    'jam_masuk'     => $jamMasuk,
                    'jam_pulang'    => $jamPulang,
                    'status'        => $statusMasuk,
                    'metode_masuk'  => 'rfid_tap',
                    'metode_pulang' => $jamPulang ? 'rfid_tap' : null,
                ]);
            } else {
                $updated = false;

                // Update jam_masuk jika belum terisi
                if (empty($presensi->jam_masuk)) {
                    $presensi->jam_masuk = $jamMasuk;
                    $presensi->status = $statusMasuk;
                    $presensi->metode_masuk = 'rfid_tap';
                    $updated = true;
                }

                // Cek pembaruan jam pulang dari tap terakhir
                if ($tapList->count() > 1) {
                    $lastTap = $tapList->last();
                    $baseTime = $presensi->jam_masuk ? "{$tapDate} {$presensi->jam_masuk}" : $firstTap->waktu_tap;
                    $diffSec = Carbon::parse($lastTap->waktu_tap)->diffInSeconds(Carbon::parse($baseTime));

                    if ($diffSec >= 180) {
                        $newJamPulang = Carbon::parse($lastTap->waktu_tap)->format('H:i:s');
                        if (empty($presensi->jam_pulang) || $newJamPulang > $presensi->jam_pulang) {
                            $presensi->jam_pulang = $newJamPulang;
                            $presensi->metode_pulang = 'rfid_tap';
                            $updated = true;
                        }
                    }
                }

                if ($updated) {
                    $presensi->save();
                }
            }
        }
    }
}
