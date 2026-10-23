<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\PresensiGuru;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RekapAbsensiController extends Controller
{
    /**
     * Submenu: Rekap Absensi Guru.
     */
    public function guru(Request $request)
    {
        $user = auth()->user();
        $lembagas = $user->accessibleLembagas();

        // Filter Lembaga
        $lembagaId = $request->get('lembaga_id');
        if (!$lembagaId) {
            $currentLembaga = $user->currentLembaga() ?: $lembagas->first();
            $lembagaId = $currentLembaga ? $currentLembaga->id : null;
        }

        // Filter Periode
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $tanggal = $request->get('tanggal');
        $status = $request->get('status');
        $search = $request->get('search');

        // Query Presensi
        $query = PresensiGuru::with(['user', 'lembaga']);

        if ($lembagaId) {
            $query->where('lembaga_id', $lembagaId);
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

        $presensis = $query->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'asc')
            ->paginate(20)
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
            'keterangan' => 'nullable|string|max:500',
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
        $lembagaId = $request->get('lembaga_id');
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);

        $query = PresensiGuru::with(['user', 'lembaga'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun);

        if ($lembagaId) {
            $query->where('lembaga_id', $lembagaId);
        }

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
        $lembagaId = $request->get('lembaga_id');
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);

        $lembaga = Lembaga::find($lembagaId) ?: (auth()->user()->currentLembaga() ?: Lembaga::first());

        $query = PresensiGuru::with('user')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun);

        if ($lembaga) {
            $query->where('lembaga_id', $lembaga->id);
        }

        $records = $query->orderBy('tanggal', 'asc')->orderBy('jam_masuk', 'asc')->get();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$bulan] ?? '';

        return view('rekap-absensi.cetak', compact('records', 'lembaga', 'bulan', 'tahun', 'namaBulan'));
    }
}
