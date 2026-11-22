<?php

namespace App\Http\Controllers;

use App\Http\Requests\PendaftaranRequest;
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
        $penerimaan = PenerimaanSantri::with(['gelombangs' => fn ($q) => $q->withCount('pendaftarans')])
            ->where('slug', $slug)
            ->firstOrFail();

        $isOpen = $penerimaan->isOpen();
        $activeGelombang = $penerimaan->activeGelombang();
        $statusState = $penerimaan->statusState();
        $allowedJenjangs = $penerimaan->allowedJenjangs();
        $dokumenConfig = $penerimaan->dokumenConfig();

        return view('spmb.form', compact(
            'penerimaan',
            'isOpen',
            'activeGelombang',
            'statusState',
            'allowedJenjangs',
            'dokumenConfig'
        ));
    }

    public function store(Request $request, string $slug, ImageCompressor $compressor)
    {
        $penerimaan = PenerimaanSantri::with(['gelombangs' => fn ($q) => $q->withCount('pendaftarans')])
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
        $allowedJenjangs = $penerimaan->allowedJenjangs();
        $dokumenConfig = $penerimaan->dokumenConfig();

        // Base rules & labels dari PendaftaranRequest agar konsisten
        $baseRequest = new PendaftaranRequest();
        $rules = $baseRequest->rules();
        $attributes = $baseRequest->attributes();
        $messages = $baseRequest->messages();

        // Override aturan khusus pendaftaran publik
        $rules['jenjang'] = ['required', Rule::in($allowedJenjangs)];
        $rules['nama_lengkap'] = 'required|string|max:255';
        $rules['jenis_kelamin'] = 'required|in:Laki-laki,Perempuan';
        $rules['pj_biaya_hp'] = 'required|string|max:30';
        $rules['nik'] = [
            'nullable',
            'string',
            'max:16',
            $request->filled('nik') ? Rule::unique('pendaftarans', 'nik') : null,
        ];
        $rules['nik'] = array_values(array_filter($rules['nik']));

        // Terapkan aturan wajib/opsional/tidak untuk foto berkas sesuai pengaturan event
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

        // Jika event hanya 1 jenjang, pastikan terisi otomatis
        if (count($allowedJenjangs) === 1 && empty($input['jenjang'])) {
            $input['jenjang'] = $allowedJenjangs[0];
        }

        $validator = Validator::make($input, $rules, $messages, $attributes);
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        $data = $validator->validated();

        // Kompres & simpan foto berkas
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

        // Normalisasi nomor WA
        foreach (['pj_biaya_hp', 'ayah_hp', 'ibu_hp'] as $hpField) {
            if (!empty($data[$hpField])) {
                $data[$hpField] = preg_replace('/[^0-9+]/', '', (string) $data[$hpField]);
            }
        }

        $data['penerimaan_santri_id'] = $penerimaan->id;
        $data['penerimaan_gelombang_id'] = $activeGelombang?->id;
        $data['status_pendaftaran'] = 'Pending';
        $data['no_induk'] = $this->generateRegistrationNumber();
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

        $waMessage = "Assalamu'alaikum Panitia {$penerimaan->judul},\n"
            . "Saya telah mendaftar secara online:\n"
            . "No. Registrasi: {$pendaftaran->no_induk}\n"
            . "Nama: {$pendaftaran->nama_lengkap}\n"
            . "Jenjang: {$pendaftaran->jenjang}";
        $waConfirmUrl = $penerimaan->waUrl($waMessage);

        return view('spmb.bukti', compact('penerimaan', 'pendaftaran', 'waConfirmUrl'));
    }

    public function cetak(string $slug, string $token)
    {
        $penerimaan = PenerimaanSantri::where('slug', $slug)->firstOrFail();
        $pendaftaran = Pendaftaran::with('gelombang')
            ->where('penerimaan_santri_id', $penerimaan->id)
            ->where('public_token', $token)
            ->firstOrFail();

        return view('spmb.cetak', compact('penerimaan', 'pendaftaran'));
    }

    public function cekStatus(Request $request, string $slug)
    {
        $penerimaan = PenerimaanSantri::where('slug', $slug)->firstOrFail();
        $kode = trim((string) $request->query('kode', ''));
        $pendaftaran = null;
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
        }

        return view('spmb.cek-status', compact('penerimaan', 'kode', 'pendaftaran', 'searched'));
    }

    protected function generateRegistrationNumber(): string
    {
        $tahun = date('Y');
        $seq = Pendaftaran::whereYear('created_at', $tahun)->count() + 1;

        do {
            $code = 'SPMB-' . $tahun . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $exists = Pendaftaran::where('no_induk', $code)->exists();
            $seq++;
        } while ($exists);

        return $code;
    }
}
