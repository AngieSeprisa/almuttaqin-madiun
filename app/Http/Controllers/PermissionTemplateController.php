<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\PermissionTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = PermissionTemplate::query()->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where('nama', 'like', "%{$search}%");
        }

        $templates = $query->paginate(15)->withQueryString();
        $lembagaMap = Lembaga::orderBy('urutan')->get()->keyBy('id');

        return view('template-akses.index', compact('templates', 'lembagaMap'));
    }

    public function create()
    {
        $template = new PermissionTemplate([
            'lembaga_ids' => [],
            'permissions' => [],
        ]);

        return view('template-akses.form', [
            'template'   => $template,
            'isEdit'     => false,
            'lembagas'   => Lembaga::where('is_active', true)->orderBy('urutan')->get(),
            'menuGroups' => UserController::systemMenuGroups(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateTemplate($request);

        $template = PermissionTemplate::create([
            'nama'         => $validated['nama'],
            'default_role' => $validated['default_role'] ?? null,
            'lembaga_ids'  => $this->normalizeLembagaIds($validated['lembagas'] ?? []),
            'permissions'  => $this->normalizePermissions($request->input('permissions', [])),
            'created_by'   => auth()->id(),
        ]);

        return redirect()->route('template-akses.index')
            ->with('success', 'Template "' . $template->nama . '" berhasil dibuat.');
    }

    public function edit(PermissionTemplate $templateAkses)
    {
        return view('template-akses.form', [
            'template'   => $templateAkses,
            'isEdit'     => true,
            'lembagas'   => Lembaga::where('is_active', true)->orderBy('urutan')->get(),
            'menuGroups' => UserController::systemMenuGroups(),
        ]);
    }

    public function update(Request $request, PermissionTemplate $templateAkses)
    {
        $validated = $this->validateTemplate($request, $templateAkses->id);

        $templateAkses->update([
            'nama'         => $validated['nama'],
            'default_role' => $validated['default_role'] ?? null,
            'lembaga_ids'  => $this->normalizeLembagaIds($validated['lembagas'] ?? []),
            'permissions'  => $this->normalizePermissions($request->input('permissions', [])),
        ]);

        return redirect()->route('template-akses.index')
            ->with('success', 'Template "' . $templateAkses->nama . '" berhasil diperbarui.');
    }

    public function destroy(PermissionTemplate $templateAkses)
    {
        $nama = $templateAkses->nama;
        $templateAkses->delete();

        return redirect()->route('template-akses.index')
            ->with('success', 'Template "' . $nama . '" berhasil dihapus.');
    }

    protected function validateTemplate(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nama'         => [
                'required',
                'string',
                'max:120',
                Rule::unique('permission_templates', 'nama')->ignore($ignoreId),
            ],
            'default_role' => 'nullable|in:yayasan,admin_lembaga,guru',
            'lembagas'     => 'nullable|array',
            'lembagas.*'   => 'integer|exists:lembagas,id',
            'permissions'  => 'nullable|array',
        ], [
            'nama.required' => 'Nama template wajib diisi.',
            'nama.unique'   => 'Nama template sudah digunakan.',
        ]);
    }

    protected function normalizeLembagaIds(array $ids): array
    {
        return array_values(array_unique(array_map('intval', $ids)));
    }

    protected function normalizePermissions(array $input): array
    {
        $validMenus = array_keys(UserController::systemMenus());
        $validActions = ['view', 'create', 'edit', 'delete'];
        $normalized = [];

        foreach ($validMenus as $code) {
            $menuInput = $input[$code] ?? null;
            if (!is_array($menuInput)) {
                continue;
            }

            $actions = [];
            foreach ($validActions as $act) {
                if (!empty($menuInput[$act])) {
                    $actions[] = $act;
                }
            }

            if (!empty($actions)) {
                if (!in_array('view', $actions, true)) {
                    array_unshift($actions, 'view');
                }
                $normalized[$code] = $actions;
            }
        }

        return $normalized;
    }
}
