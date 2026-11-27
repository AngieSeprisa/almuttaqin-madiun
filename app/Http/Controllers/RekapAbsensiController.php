<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\Presensi;
use App\Models\PresensiGuru;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RekapAbsensiController extends Controller
{
    /**
     * Tentukan daftar lembaga yang diizinkan dan filter lembaga terpilih secara ketat sesuai room aktif.
     */
    protected function resolveScopedLembagaFilter(User $user, Request $request): array
    {
        $scopedFormal = Lembaga::activeScopedFormal($user);
        $isSemuaRequested = $request->exists('lembaga_id') && (
            $request->get('lembaga_id') === '' ||
            $request->get('lembaga_id') === 'all' ||
            is_null($request->get('lembaga_id'))
        );

        if ($user->isYayasan()) {
            $lembagas = $user->accessibleLembagas();
            $defaultLembaga = $scopedFormal ?: ($user->currentLembaga() ?: $lembagas->first());
            $defaultLembagaId = $defaultLembaga?->id;

            if ($isSemuaRequested) {
                $lembagaId = null;
            } elseif ($request->filled('lembaga_id')) {
                $lembagaId = (int) $request->get('lembaga_id');
            } else {
                $lembagaId = $defaultLembagaId;
            }

            return [$lembagas, $lembagaId, $defaultLembagaId, null, $scopedFormal];
        }

        // Non-Yayasan di room formal: terkunci ketat pada lembaga room aktifnya
        if ($scopedFormal) {
            return [collect([$scopedFormal]), $scopedFormal->id, $scopedFormal->id, [$scopedFormal->id], $scopedFormal];
        }

        $lembagas = $user->accessibleLembagas();
        $allowedIds = $lembagas->pluck('id')->map(fn ($id) => (int) $id)->all();
        $defaultLembaga = $user->currentLembaga() ?: $lembagas->first();
        $defaultLembagaId = $defaultLembaga?->id;

        if ($isSemuaRequested && count($allowedIds) > 1) {
            $lembagaId = null;
        } elseif ($request->filled('lembaga_id') && in_array((int) $request->get('lembaga_id'), $allowedIds, true)) {
            $lembagaId = (int) $request->get('lembaga_id');
        } else {
            $lembagaId = $defaultLembagaId;
        }

        return [$lembagas, $lembagaId, $defaultLembagaId, $allowedIds, $scopedFormal];
    }

    /**
     * Submenu: Rekap Absensi Guru.
     */
    public function guru(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();
        [$lembagas, $lembagaId, $defaultLembagaId, $allowedLembagaIds, $scopedFormal] = $this->resolveScopedLembagaFilter($user, $request);

        // Filter Periode (Default otomatis ke tanggal hari ini)
        $todayStr = Carbon::today()->toDateString();
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $tanggal = $request->exists('tanggal') ? $request->get('tanggal') : $todayStr;
        $status = $request->get('status');
        $search = $request->get('search');

        // Sinkronisasi otomatis data tap RFID multi-lokasi ke presensi guru
        try {
            $this->syncRfidTapsToPresensiGuru($tanggal, $bulan, $tahun);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal sinkronisasi RFID tap ke presensi guru: ' . $e->getMessage());
        }

        // Data presensi milik akun yang sedang login pada tanggal terpilih
        $presensiSaya = $user ? PresensiGuru::where('user_id', $user->id)
            ->whereDate('tanggal', $tanggal ?: $todayStr)
            ->orderByDesc('id')
            ->first() : null;

        // Query Presensi Terpadu yang Disesuaikan Ketat Berdasarkan Lembaga Terdaftar Guru
        $query = $this->buildPresensiQuery($lembagaId, $tanggal, $bulan, $tahun, $status, $search, $allowedLembagaIds);

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
            ->get();

        return view('rekap-absensi.guru', compact(
            'presensis',
            'presensiSaya',
            'lembagas',
            'lembagaId',
            'defaultLembagaId',
            'scopedFormal',
            'todayStr',
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

        /** @var User|null $authUser */
        $authUser = auth()->user();
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($authUser && !$authUser->isYayasan()) {
            $allowedIds = $scopedFormal
                ? [$scopedFormal->id]
                : $authUser->accessibleLembagas()->pluck('id')->map(fn ($i) => (int) $i)->all();
            if (!in_array((int) $presensi->lembaga_id, $allowedIds, true)) {
                abort(403, 'Anda tidak memiliki akses untuk mengoreksi presensi guru lembaga lain.');
            }
        }

        $ketBaru = isset($validated['keterangan']) ? trim($validated['keterangan']) : '';
        $keteranganFinal = $presensi->keterangan;
        if ($ketBaru !== '' && $ketBaru !== (string) $presensi->keterangan) {
            $keteranganFinal = str_starts_with($ketBaru, 'Koreksi:')
                ? $ketBaru
                : ($presensi->keterangan ? $presensi->keterangan . ' | Koreksi: ' . $ketBaru : 'Koreksi: ' . $ketBaru);
        }

        $presensi->update([
            'status'     => $validated['status'],
            'jam_masuk'  => $validated['jam_masuk'] ? $validated['jam_masuk'] . ':00' : $presensi->jam_masuk,
            'jam_pulang' => $validated['jam_pulang'] ? $validated['jam_pulang'] . ':00' : $presensi->jam_pulang,
            'keterangan' => $keteranganFinal,
        ]);

        return redirect()->back()->with('success', 'Data presensi berhasil dikoreksi.');
    }

    /**
     * Resolusi periode ekspor/cetak (harian, mingguan, atau bulanan) menjadi rentang tanggal & label.
     *
     * @return array{periode:string, start:Carbon, end:Carbon, label:string, file_suffix:string, bulan:int, tahun:int}
     */
    protected function resolvePeriodRange(Request $request): array
    {
        $periode = strtolower(trim((string) $request->get('periode', '')));
        $tanggalParam = $request->get('tanggal');
        $refDate = !empty($tanggalParam)
            ? Carbon::parse($tanggalParam)
            : Carbon::today();

        if (!in_array($periode, ['harian', 'mingguan', 'bulanan'], true)) {
            $periode = !empty($tanggalParam) ? 'harian' : 'bulanan';
        }

        if ($periode === 'mingguan') {
            $mingguParam = trim((string) $request->get('minggu', ''));
            $startParam = trim((string) $request->get('tanggal_mulai', ''));
            $endParam = trim((string) $request->get('tanggal_selesai', ''));

            if ($mingguParam !== '' && preg_match('/^(\d{4})-W(\d{2})$/i', $mingguParam, $m)) {
                $start = Carbon::now()->setISODate((int) $m[1], (int) $m[2], 1)->startOfDay();
                $end = $start->copy()->addDays(6)->endOfDay();
            } elseif ($startParam !== '' && $endParam !== '') {
                $start = Carbon::parse($startParam)->startOfDay();
                $end = Carbon::parse($endParam)->endOfDay();
                if ($end->lt($start)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
            } else {
                $start = $refDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $end = $refDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            }

            $label = 'Mingguan (' . $start->copy()->locale('id')->translatedFormat('d M Y') . ' - ' . $end->copy()->locale('id')->translatedFormat('d M Y') . ')';
            $suffix = 'mingguan_' . $start->format('Ymd') . '_' . $end->format('Ymd');

            return [
                'periode'     => 'mingguan',
                'start'       => $start,
                'end'         => $end,
                'label'       => $label,
                'file_suffix' => $suffix,
                'bulan'       => (int) $start->month,
                'tahun'       => (int) $start->year,
            ];
        }

        if ($periode === 'bulanan') {
            $bulanTahunParam = trim((string) $request->get('bulan_tahun', ''));
            if ($bulanTahunParam !== '' && preg_match('/^(\d{4})-(\d{2})$/', $bulanTahunParam, $m)) {
                $tahun = (int) $m[1];
                $bulan = max(1, min(12, (int) $m[2]));
            } else {
                $tahun = (int) $request->get('tahun', $refDate->year);
                $bulan = max(1, min(12, (int) $request->get('bulan', $refDate->month)));
            }

            $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
            $end = $start->copy()->endOfMonth()->endOfDay();
            $label = 'Bulanan (' . $start->copy()->locale('id')->translatedFormat('F Y') . ')';
            $suffix = 'bulanan_' . $tahun . '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

            return [
                'periode'     => 'bulanan',
                'start'       => $start,
                'end'         => $end,
                'label'       => $label,
                'file_suffix' => $suffix,
                'bulan'       => $bulan,
                'tahun'       => $tahun,
            ];
        }

        // Harian
        $start = $refDate->copy()->startOfDay();
        $end = $refDate->copy()->endOfDay();
        $label = 'Harian (' . $start->copy()->locale('id')->translatedFormat('d F Y') . ')';
        $suffix = 'harian_' . $start->format('Ymd');

        return [
            'periode'     => 'harian',
            'start'       => $start,
            'end'         => $end,
            'label'       => $label,
            'file_suffix' => $suffix,
            'bulan'       => (int) $start->month,
            'tahun'       => (int) $start->year,
        ];
    }

    /**
     * Ekspor Rekap Absensi Guru ke Excel (.xlsx) mendukung periode Harian, Mingguan, dan Bulanan.
     */
    public function export(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();
        [$lembagas, $lembagaId, $defaultLembagaId, $allowedLembagaIds] = $this->resolveScopedLembagaFilter($user, $request);

        $period = $this->resolvePeriodRange($request);
        $status = $request->get('status');
        $search = $request->get('search');

        // Sinkronisasi seluruh tap RFID dalam rentang periode terpilih
        $this->syncRfidTapsToPresensiGuru(null, $period['bulan'], $period['tahun'], $period['start'], $period['end']);

        $query = $this->buildPresensiQuery(
            $lembagaId,
            null,
            $period['bulan'],
            $period['tahun'],
            $status,
            $search,
            $allowedLembagaIds,
            $period['start'],
            $period['end']
        );
        $records = $query->orderBy('tanggal', 'asc')->orderBy('jam_masuk', 'asc')->get();

        $lembagaObj = $lembagaId ? Lembaga::find($lembagaId) : null;
        $lembagaLabel = $lembagaObj ? $lembagaObj->nama : 'Semua Lembaga';

        $spreadsheet = new Spreadsheet();
        $headerFillStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0052FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $thinBorderStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ];

        // Jika periode mingguan atau bulanan, buat Sheet 1: Ringkasan Per Guru lalu Sheet 2: Detail Harian
        if (in_array($period['periode'], ['mingguan', 'bulanan'], true)) {
            $summarySheet = $spreadsheet->getActiveSheet();
            $summarySheet->setTitle('Ringkasan Per Guru');

            $summarySheet->setCellValue('A1', 'YAYASAN PONDOK PESANTREN AL-MUTTAQIN');
            $summarySheet->setCellValue('A2', 'REKAPITULASI ABSENSI GURU - ' . strtoupper($period['label']));
            $summarySheet->setCellValue('A3', 'Lembaga: ' . $lembagaLabel . ' | Dicetak: ' . Carbon::now('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB');
            $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $summarySheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
            $summarySheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB('475569');

            $sumHeaders = ['No', 'NIP', 'Nama Guru', 'Lembaga', 'Tepat Waktu', 'Terlambat', 'Izin', 'Sakit', 'Alpa', 'Total Hari'];
            $col = 'A';
            foreach ($sumHeaders as $h) {
                $summarySheet->setCellValue($col . '5', $h);
                $col++;
            }
            $summarySheet->getStyle('A5:J5')->applyFromArray($headerFillStyle);
            $summarySheet->getRowDimension(5)->setRowHeight(24);

            // Kelompokkan data per guru (dan lembaga)
            $grouped = $records->groupBy(fn ($r) => ($r->user_id ?? 0) . '_' . ($r->lembaga_id ?? 0))
                ->sortBy(fn ($items) => strtolower($items->first()->user?->name ?? ''));

            $rIdx = 6;
            $no = 1;
            $totHadir = $totTelat = $totIzin = $totSakit = $totAlpa = $totSemua = 0;

            foreach ($grouped as $items) {
                $first = $items->first();
                $cHadir = $items->where('status', 'hadir')->count();
                $cTelat = $items->where('status', 'terlambat')->count();
                $cIzin = $items->where('status', 'izin')->count();
                $cSakit = $items->where('status', 'sakit')->count();
                $cAlpa = $items->where('status', 'alpa')->count();
                $cTotal = $items->count();

                $totHadir += $cHadir;
                $totTelat += $cTelat;
                $totIzin += $cIzin;
                $totSakit += $cSakit;
                $totAlpa += $cAlpa;
                $totSemua += $cTotal;

                $summarySheet->setCellValue('A' . $rIdx, $no++);
                $summarySheet->setCellValueExplicit('B' . $rIdx, (string) ($first->user?->nip ?: '-'), DataType::TYPE_STRING);
                $summarySheet->setCellValue('C' . $rIdx, $first->user?->name ?? '-');
                $summarySheet->setCellValue('D' . $rIdx, $first->lembaga?->nama ?? '-');
                $summarySheet->setCellValue('E' . $rIdx, $cHadir);
                $summarySheet->setCellValue('F' . $rIdx, $cTelat);
                $summarySheet->setCellValue('G' . $rIdx, $cIzin);
                $summarySheet->setCellValue('H' . $rIdx, $cSakit);
                $summarySheet->setCellValue('I' . $rIdx, $cAlpa);
                $summarySheet->setCellValue('J' . $rIdx, $cTotal);

                $summarySheet->getStyle("A{$rIdx}:B{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $summarySheet->getStyle("E{$rIdx}:J{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $summarySheet->getStyle("A{$rIdx}:J{$rIdx}")->applyFromArray($thinBorderStyle);
                $rIdx++;
            }

            // Baris total keseluruhan
            $summarySheet->setCellValue('A' . $rIdx, '');
            $summarySheet->setCellValue('C' . $rIdx, 'TOTAL');
            $summarySheet->setCellValue('E' . $rIdx, $totHadir);
            $summarySheet->setCellValue('F' . $rIdx, $totTelat);
            $summarySheet->setCellValue('G' . $rIdx, $totIzin);
            $summarySheet->setCellValue('H' . $rIdx, $totSakit);
            $summarySheet->setCellValue('I' . $rIdx, $totAlpa);
            $summarySheet->setCellValue('J' . $rIdx, $totSemua);
            $summarySheet->getStyle("A{$rIdx}:J{$rIdx}")->getFont()->setBold(true);
            $summarySheet->getStyle("A{$rIdx}:J{$rIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            $summarySheet->getStyle("E{$rIdx}:J{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $summarySheet->getStyle("A{$rIdx}:J{$rIdx}")->applyFromArray($thinBorderStyle);

            foreach (range('A', 'J') as $c) {
                $summarySheet->getColumnDimension($c)->setAutoSize(true);
            }

            $detailSheet = $spreadsheet->createSheet();
            $detailSheet->setTitle('Detail Harian');
        } else {
            $detailSheet = $spreadsheet->getActiveSheet();
            $detailSheet->setTitle('Detail Harian');
        }

        // Isi Sheet Detail Harian
        $detailSheet->setCellValue('A1', 'YAYASAN PONDOK PESANTREN AL-MUTTAQIN');
        $detailSheet->setCellValue('A2', 'DETAIL ABSENSI GURU - ' . strtoupper($period['label']));
        $detailSheet->setCellValue('A3', 'Lembaga: ' . $lembagaLabel);
        $detailSheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $detailSheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $detailSheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB('475569');

        $detailHeaders = ['No', 'Tanggal', 'Hari', 'NIP', 'Nama Guru', 'Lembaga', 'Jam Masuk', 'Jam Pulang', 'Status', 'Jarak Masuk (m)', 'Keterangan'];
        $col = 'A';
        foreach ($detailHeaders as $h) {
            $detailSheet->setCellValue($col . '5', $h);
            $col++;
        }
        $detailSheet->getStyle('A5:K5')->applyFromArray($headerFillStyle);
        $detailSheet->getRowDimension(5)->setRowHeight(24);

        $rowNum = 6;
        $no = 1;
        foreach ($records as $row) {
            $tglObj = $row->tanggal
                ? ($row->tanggal instanceof Carbon ? $row->tanggal : Carbon::parse($row->tanggal))
                : null;

            $detailSheet->setCellValue('A' . $rowNum, $no++);
            $detailSheet->setCellValue('B' . $rowNum, $tglObj ? $tglObj->format('d/m/Y') : '-');
            $detailSheet->setCellValue('C' . $rowNum, $tglObj ? $tglObj->copy()->locale('id')->translatedFormat('l') : '-');
            $detailSheet->setCellValueExplicit('D' . $rowNum, (string) ($row->user?->nip ?: '-'), DataType::TYPE_STRING);
            $detailSheet->setCellValue('E' . $rowNum, $row->user?->name ?? '-');
            $detailSheet->setCellValue('F' . $rowNum, $row->lembaga?->nama ?? '-');
            $detailSheet->setCellValue('G' . $rowNum, $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-');
            $detailSheet->setCellValue('H' . $rowNum, $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-');
            $detailSheet->setCellValue('I' . $rowNum, $row->status_label);
            $detailSheet->setCellValue('J' . $rowNum, $row->jarak_masuk_meter ?? '-');
            $detailSheet->setCellValue('K' . $rowNum, $row->keterangan ?? '-');

            $detailSheet->getStyle("A{$rowNum}:D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $detailSheet->getStyle("G{$rowNum}:J{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $detailSheet->getStyle("A{$rowNum}:K{$rowNum}")->applyFromArray($thinBorderStyle);
            $rowNum++;
        }

        foreach (range('A', 'K') as $c) {
            $detailSheet->getColumnDimension($c)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $fileName = 'rekap_absensi_guru_' . $period['file_suffix'] . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Cetak Lembar Rekap Absensi Guru (Format A4 PDF/Print Friendly).
     */
    public function cetak(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();
        [$lembagas, $lembagaId, $defaultLembagaId, $allowedLembagaIds] = $this->resolveScopedLembagaFilter($user, $request);

        $period = $this->resolvePeriodRange($request);
        $bulan = $period['bulan'];
        $tahun = $period['tahun'];

        $this->syncRfidTapsToPresensiGuru(null, $bulan, $tahun, $period['start'], $period['end']);

        $lembaga = $lembagaId ? Lembaga::find($lembagaId) : ($user->currentLembaga() ?: Lembaga::first());

        $query = $this->buildPresensiQuery(
            $lembagaId,
            null,
            $bulan,
            $tahun,
            $request->get('status'),
            $request->get('search'),
            $allowedLembagaIds,
            $period['start'],
            $period['end']
        );
        $records = $query->orderBy('tanggal', 'asc')->orderBy('jam_masuk', 'asc')->get();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$bulan] ?? '';
        $periodeLabel = $period['label'];

        return view('rekap-absensi.cetak', compact('records', 'lembaga', 'bulan', 'tahun', 'namaBulan', 'periodeLabel'));
    }

    /**
     * Query builder presensi guru yang disesuaikan ketat berdasarkan lembaga tempat guru terdaftar:
     * - Walaupun akun berstatus yayasan, jika terdaftarnya sebagai guru SMP,
     *   maka absensinya HANYA terlihat saat memilih lembaga SMP.
     * - Tidak akan bocor ke SMA atau lembaga lainnya.
     * - Opsi "Semua Lembaga" tetap menampilkan seluruh guru sesuai hak akses lembaga user.
     */
    protected function buildPresensiQuery(
        ?int $lembagaId,
        ?string $tanggal,
        int $bulan,
        int $tahun,
        ?string $status = null,
        ?string $search = null,
        ?array $allowedLembagaIds = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ) {
        $query = PresensiGuru::with(['user', 'lembaga']);

        if ($lembagaId) {
            $query->where('lembaga_id', $lembagaId);
        } elseif ($allowedLembagaIds !== null) {
            if (empty($allowedLembagaIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('lembaga_id', $allowedLembagaIds);
            }
        }

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);
        } elseif ($tanggal) {
            $query->whereDate('tanggal', $tanggal);
        } else {
            $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
        }

        if ($status && in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($outer) use ($search) {
                $outer->where('keterangan', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%")
                            ->orWhere('jabatan', 'like', "%{$search}%")
                            ->orWhere('mata_pelajaran', 'like', "%{$search}%")
                            ->orWhere('rfid_uid', 'like', "%{$search}%");
                      });
            });
        }

        return $query;
    }

    /**
     * Sinkronisasi data tap RFID guru/pegawai dari tabel presensis ke presensi_gurus.
     * Memastikan tap yang tercatat di dashboard RFID multi-lokasi otomatis masuk ke rekap absensi guru
     * dan dipetakan ke lembaga tempat guru tersebut terdaftar.
     */
    protected function syncRfidTapsToPresensiGuru(
        ?string $tanggal = null,
        ?int $bulan = null,
        ?int $tahun = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): void {
        try {
            if (!Schema::hasTable('presensis') || !Schema::hasTable('presensi_gurus')) {
                return;
            }

            $tapsQuery = Presensi::where('tipe_entitas', 'guru_pegawai');

            if ($startDate && $endDate) {
                $tapsQuery->whereBetween('waktu_tap', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
            } elseif ($tanggal) {
                $tapsQuery->whereDate('waktu_tap', $tanggal);
            } else {
                // Batasi sinkronisasi ke hari ini agar performa tetap ringan dan cepat
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

            $fallbackLembaga = Lembaga::formalOptions()->first() ?: Lembaga::first();

            foreach ($grouped as $key => $tapList) {
                try {
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

                    // Resolusi lembaga terdaftar guru secara cerdas (prioritaskan mapel, jabatan, dan relasi sekolah)
                    $schoolLembaga = $user->resolveSchoolLembaga($firstTap->device_id);
                    $registeredLembagaId = $schoolLembaga ? $schoolLembaga->id : ($user->primary_lembaga_id ?: ($user->lembagas()->first()?->id ?: ($fallbackLembaga?->id ?? 1)));

                    // Validasi lembaga_id ada di database untuk mencegah error foreign key
                    if (!Lembaga::where('id', $registeredLembagaId)->exists()) {
                        $registeredLembagaId = $fallbackLembaga?->id;
                    }
                    if (!$registeredLembagaId) {
                        continue;
                    }

                    // Jika guru sekolah teridentifikasi sebagai lembaga formal dan primary_lembaga_id belum sesuai, selaraskan
                    if ($schoolLembaga && $schoolLembaga->isFormalSchool()) {
                        if ($user->primary_lembaga_id !== $schoolLembaga->id && !$user->isYayasan()) {
                            $user->primary_lembaga_id = $schoolLembaga->id;
                            $user->save();
                        }
                    }

                    // Jam pulang jika ada tap berikutnya yang berselisih waktu >= 3 menit
                    $jamPulang = null;
                    if ($tapList->count() > 1) {
                        $lastTap = $tapList->last();
                        $diffSec = Carbon::parse($lastTap->waktu_tap)->diffInSeconds(Carbon::parse($firstTap->waktu_tap));
                        if ($diffSec >= 180) { // Debounce 3 menit
                            $jamPulang = Carbon::parse($lastTap->waktu_tap)->format('H:i:s');
                        }
                    }

                    // Cek apakah presensi guru untuk user ini di tanggal ini sudah ada
                    $presensi = PresensiGuru::where('user_id', $userId)
                        ->whereDate('tanggal', $tapDate)
                        ->first();

                    if (!$presensi) {
                        PresensiGuru::create([
                            'user_id'       => $userId,
                            'lembaga_id'    => $registeredLembagaId,
                            'tanggal'       => $tapDate,
                            'jam_masuk'     => $jamMasuk,
                            'jam_pulang'    => $jamPulang,
                            'status'        => $statusMasuk,
                            'metode_masuk'  => 'rfid_tap',
                            'metode_pulang' => $jamPulang ? 'rfid_tap' : null,
                        ]);
                    } else {
                        $updates = [];

                        // Pastikan lembaga_id presensi guru selalu sesuai dengan lembaga tempat guru terdaftar
                        if ($presensi->lembaga_id != $registeredLembagaId) {
                            $duplicateExists = PresensiGuru::where('user_id', $userId)
                                ->whereDate('tanggal', $tapDate)
                                ->where('lembaga_id', $registeredLembagaId)
                                ->where('id', '!=', $presensi->id)
                                ->exists();
                            if (!$duplicateExists) {
                                $updates['lembaga_id'] = $registeredLembagaId;
                            }
                        }

                        // Update jam_masuk jika belum terisi
                        if (empty($presensi->jam_masuk)) {
                            $updates['jam_masuk'] = $jamMasuk;
                            $updates['status'] = $statusMasuk;
                            $updates['metode_masuk'] = 'rfid_tap';
                        }

                        // Cek pembaruan jam pulang dari tap terakhir
                        if ($jamPulang && (empty($presensi->jam_pulang) || $jamPulang > $presensi->jam_pulang)) {
                            $updates['jam_pulang'] = $jamPulang;
                            $updates['metode_pulang'] = 'rfid_tap';
                        }

                        if (!empty($updates)) {
                            $presensi->update($updates);
                        }
                    }
                } catch (\Throwable $rowEx) {
                    \Illuminate\Support\Facades\Log::warning("Gagal memproses tap RFID untuk key {$key}: " . $rowEx->getMessage());
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error syncRfidTapsToPresensiGuru: ' . $e->getMessage());
        }
    }
}
