<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\ModuleSetting;
use App\Services\ModuleSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleSettingController extends Controller
{
    public function index(ModuleSettingService $service)
    {
        return view('admin.module-settings.index', [
            'modules' => $service->statesFor(auth()->user()),
        ]);
    }

    public function update(Request $request, ModuleSettingService $service)
    {
        $catalogKeys = $service->catalog()->keys()->all();
        $data = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.status' => ['required', 'in:published,maintenance,draft'],
            'modules.*.custom_label' => ['nullable', 'string', 'max:80'],
            'modules.*.maintenance_message' => ['nullable', 'string', 'max:500'],
            'modules.*.show_desktop' => ['nullable', 'boolean'],
            'modules.*.show_mobile' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $catalogKeys) {
            foreach ($data['modules'] as $key => $values) {
                if (!in_array($key, $catalogKeys, true)) {
                    continue;
                }

                ModuleSetting::updateOrCreate(
                    ['module_key' => $key],
                    [
                        'status' => $values['status'],
                        'custom_label' => trim((string) ($values['custom_label'] ?? '')) ?: null,
                        'maintenance_message' => trim((string) ($values['maintenance_message'] ?? '')) ?: null,
                        'show_desktop' => !empty($values['show_desktop']),
                        'show_mobile' => !empty($values['show_mobile']),
                        'updated_by' => auth()->id(),
                    ]
                );
            }
        });

        return redirect()->route('admin.module-settings.index')
            ->with('success', 'Pengaturan modul berhasil diperbarui.');
    }
}
