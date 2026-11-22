<?php

namespace App\Http\Controllers;

use App\Http\Requests\PendaftaranRequest;
use App\Models\Lembaga;
use App\Models\Pendaftaran;
use App\Models\PenerimaanSantri;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class SpmbPublicController extends Controller
{
    public function show(string $slug)
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')
            ->where('slug', $slug)
            ->firstOrFail();

        $isOpen = $penerimaan->isOpen();
        $activeGelombang = $penerimaan->activeGelombang();
        $statusState = $penerimaan->statusState();
        $allowedJenjangs = $penerimaan->allowedJenjangs();
        $availableJenjangs = $penerimaan->availableJenjangs();
        $dokumenConfig = $penerimaan->dokumenConfig();
        $lokasiMap = $penerimaan->lokasiMap();

        return view('spmb.form', compact(
            'penerimaan',
            'isOpen',
            'activeGelombang',
            'statusState',
            'allowedJenjangs',
            'availableJenjangs',
            'dokumenConfig',
            'lokasiMap'
        ));
    }

    public function store(Request $request, string $slug, ImageCompressor $compressor)
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')
            ->where('slug', $slug)
            ->firstOrFail();

        // Honeypot anti-spam
        if ($request->filled('website_url')) {
            return redirect()->route('spmb.show', $slug);
        }

        if (!$penerimaan->isOpen()) {
            return redirect()->route('spmb.show', $slug)
                ->with('error', 'Pendaftaran sedang ditutup.');
        }

        $activeGelombang = $penerimaan->activeGelombang();
        $availableJenjangs = $penerimaan->availableJenjangs();
        $dokumenConfig = $penerimaan->dokumenConfig();

        $baseRequest = new PendaftaranRequest();
        $rules = $baseRequest->rules();
        $attributes = $baseRequest->attributes();
        $messages = $baseRequest->messages();

        $rules['jenjang'] = ['required', Rule::in($availableJenjangs)];
        $messages['jenjang.in'] = 'Kuota untuk lembaga/jenjang yang dipilih sudah penuh atau tidak tersedia.';
        $rules['nama_lengkap'] = 'required|string|max:255';
        $rules['jenis_kelamin'] = 'required|in:Laki-laki,Perempuan';
        $rules['pj_biaya_hp'] = 'required|string|max:30';
        $rules['nik'] = array_values(array_filter([
            'nullable',
            'string',
            'max:16',
            $request->filled('nik') ? Rule::unique('pendaftarans', 'nik') : null,
        ]));

        foreach (PenerimaanSantri::DOKUMEN_META as $flagKey => $meta) {
            $fotoField = $meta['foto'];
            $mode = $dokumenConfig[$flagKey] ?? 'opsional';
            if ($mode === 'wajib') {
                $rules[$fotoField] = 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240';
            } elseif ($mode === 'tidak') {
                unset($rules[$fotoField], $rules[$flagKey]);
            }
        }

        $attributes['pj_biaya_hp'] = 'No. WhatsApp Aktif (Orang Tua / Wali)';

        $input = $request->all();
        $input['penerima_kip'] = $request->boolean('penerima_kip');
        foreach (array_keys(PenerimaanSantri::DOKUMEN_META) as $flagKey) {
            $input[$flagKey] = $request->boolean($flagKey);
        }

        if (count($availableJenjangs) === 1 && empty($input['jenjang'])) {
            $input['jenjang'] = $availableJenjangs[0];
        }

        $validator = Validator::make($input, $rules, $messages, $attributes);
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        $data = $validator->validated();

        $stored = [];
        try {
            foreach (Pendaftaran::FOTO_BERKAS as $field => [$flag]) {
                unset($data[$field]);
                if (($dokumenConfig[$flag] ?? 'opsional') === 'tidak') {
                    unset($data[$flag]);
                    continue;
                }

                if ($request->hasFile($field)) {
                    $stored[$field] = $compressor->store($request->file($field), 'berkas');
                    $data[$field] = $stored[$field];
                    $data[$flag] = true;
                }
            }
        } catch (RuntimeException $e) {
            foreach ($stored as $path) {
                $compressor->delete($path);
            }

            return back()->withInput()->withErrors(['foto_berkas' => $e->getMessage()]);
        }

        if (empty($data['nik'])) {
            $data['nik'] = null;
        }

        foreach (['pj_biaya_hp', 'ayah_hp', 'ibu_hp'] as $hpField) {
            if (!empty($data[$hpField])) {
                $data[$hpField] = preg_replace('/[^0-9+]/', '', (string) $data[$hpField]);
            }
        }

        $data['penerimaan_santri_id'] = $penerimaan->id;
        $data['penerimaan_gelombang_id'] = $activeGelombang?->id;
        $data['status_pendaftaran'] = 'Pending';
        $data['no_induk'] = $this->generateRegistrationNumber($penerimaan, $data['jenjang']);
        $data['public_token'] = Str::random(40);

        try {
            $pendaftaran = Pendaftaran::create($data);
        } catch (\Throwable $e) {
            foreach ($stored as $path) {
                $compressor->delete($path);
            }
            throw $e;
        }

        return redirect()->route('spmb.bukti', [
            'slug'  => $penerimaan->slug,
            'token' => $pendaftaran->public_token,
        ])->with('success', 'Pendaftaran berhasil dikirim.');
    }

    public function bukti(string $slug, string $token)
    {
        $penerimaan = PenerimaanSantri::where('slug', $slug)->firstOrFail();
        $pendaftaran = Pendaftaran::with('gelombang')
            ->where('penerimaan_santri_id', $penerimaan->id)
            ->where('public_token', $token)
            ->firstOrFail();

        $waMessage = "Assalamu'alaikum Panitia {$penerimaan->judul} ({$pendaftaran->jenjang}),\n"
            . "Saya telah mendaftar secara online:\n"
            . "No. Registrasi: {$pendaftaran->no_induk}\n"
            . "Nama: {$pendaftaran->nama_lengkap}\n"
            . "Jenjang: {$pendaftaran->jenjang}";
        $waConfirmUrl = $penerimaan->waUrl($waMessage, $pendaftaran->jenjang);
        $lokasiSeleksi = $penerimaan->lokasiForJenjang($pendaftaran->jenjang);

        return view('spmb.bukti', compact('penerimaan', 'pendaftaran', 'waConfirmUrl', 'lokasiSeleksi'));
    }

    public function cetak(string $slug, string $token)
    {
        $penerimaan = PenerimaanSantri::where('slug', $slug)->firstOrFail();
        $pendaftaran = Pendaftaran::with('gelombang')
            ->where('penerimaan_santri_id', $penerimaan->id)
            ->where('public_token', $token)
            ->firstOrFail();

        $kop = Lembaga::kopForJenjang($pendaftaran->jenjang);
        $lokasiSeleksi = $penerimaan->lokasiForJenjang($pendaftaran->jenjang);

        return view('spmb.cetak', compact('penerimaan', 'pendaftaran', 'kop', 'lokasiSeleksi'));
    }

    public function cekStatus(Request $request, string $slug)
    {
        $penerimaan = PenerimaanSantri::where('slug', $slug)->firstOrFail();
        $kode = trim((string) $request->query('kode', ''));
        $pendaftaran = null;
        $lokasiSeleksi = null;
        $searched = false;

        if ($kode !== '') {
            $searched = true;
            $pendaftaran = Pendaftaran::with('gelombang')
                ->where('penerimaan_santri_id', $penerimaan->id)
                ->where(function ($q) use ($kode) {
                    $q->where('no_induk', $kode)
                        ->orWhere('nik', $kode);
                })
                ->latest()
                ->first();

            if ($pendaftaran) {
                $lokasiSeleksi = $penerimaan->lokasiForJenjang($pendaftaran->jenjang);
            }
        }

        return view('spmb.cek-status', compact('penerimaan', 'kode', 'pendaftaran', 'lokasiSeleksi', 'searched'));
    }

    /**
     * Format nomor registrasi: {LEMBAGA}-{NOMOR_URUT}-{TAHUN_AJARAN}
     * Contoh: SMP-01-202728, SMA-02-202728, SMK-01-202728
     */
    protected function generateRegistrationNumber(PenerimaanSantri $penerimaan, string $jenjang): string
    {
        $lembagaCode = match (strtoupper(trim($jenjang))) {
            'REGULER' => 'PONDOK',
            default   => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $jenjang)),
        };

        $tahunCode = $penerimaan->tahunAjaranCode();
        $prefix = $lembagaCode . '-';
        $suffix = '-' . $tahunCode;

        $existingCodes = Pendaftaran::where('no_induk', 'like', $prefix . '%' . $suffix)
            ->pluck('no_induk');

        $maxSeq = 0;
        $pattern = '/^' . preg_quote($lembagaCode, '/') . '-(\d+)-' . preg_quote($tahunCode, '/') . '$/';
        foreach ($existingCodes as $code) {
            if (preg_match($pattern, (string) $code, $m)) {
                $num = (int) $m[1];
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            }
        }

        $seq = $maxSeq + 1;
        do {
            $urut = str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
            $candidate = "{$lembagaCode}-{$urut}-{$tahunCode}";
            $exists = Pendaftaran::where('no_induk', $candidate)->exists();
            $seq++;
        } while ($exists);

        return $candidate;
    }
}
