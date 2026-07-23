<?php

namespace App\Services;

use App\ModuleSetting;
use App\User;
use Illuminate\Support\Facades\Schema;

class ModuleSettingService
{
    protected $records;

    public function catalog()
    {
        return collect(config('modules.catalog', []));
    }

    public function statesFor(?User $user = null)
    {
        $records = $this->records();

        return $this->catalog()->map(function ($definition, $key) use ($records, $user) {
            $record = $records->get($key);
            $status = $record ? $record->status : ModuleSetting::STATUS_PUBLISHED;
            $isSuperAdmin = $user && $user->isSuperAdmin();
            $showDesktop = $record ? (bool) $record->show_desktop : true;
            $showMobile = $record ? (bool) $record->show_mobile : true;

            return array_merge($definition, [
                'key' => $key,
                'status' => $status,
                'label' => ($record && $record->custom_label) ? $record->custom_label : $definition['name'],
                'maintenance_message' => ($record && $record->maintenance_message)
                    ? $record->maintenance_message
                    : 'Modul sedang dalam pemeliharaan. Silakan coba kembali beberapa saat lagi.',
                'show_desktop' => $showDesktop,
                'show_mobile' => $showMobile,
                'display_order' => $record ? (int) $record->display_order : 0,
                'visible_desktop' => $isSuperAdmin || ($status === ModuleSetting::STATUS_PUBLISHED && $showDesktop),
                'visible_mobile' => $isSuperAdmin || ($status === ModuleSetting::STATUS_PUBLISHED && $showMobile),
                'accessible' => $isSuperAdmin || $status === ModuleSetting::STATUS_PUBLISHED,
                'record' => $record,
            ]);
        })->sortBy('display_order');
    }

    public function state($key, ?User $user = null)
    {
        return $this->statesFor($user)->get($key);
    }

    public function resolveRoute(?string $routeName)
    {
        if (!$routeName || strpos($routeName, 'admin.module-settings.') === 0) {
            return null;
        }

        foreach ($this->catalog() as $key => $definition) {
            if (in_array($routeName, $definition['routes'] ?? [], true)) {
                return $key;
            }
        }

        foreach ($this->catalog() as $key => $definition) {
            foreach ($definition['prefixes'] ?? [] as $prefix) {
                if (strpos($routeName, $prefix) === 0) {
                    return $key;
                }
            }
        }

        return null;
    }

    public function resolveMobileModule($module)
    {
        foreach ($this->catalog() as $key => $definition) {
            if (($definition['mobile'] ?? null) === $module) {
                return $key;
            }
        }

        return null;
    }

    protected function records()
    {
        if ($this->records !== null) {
            return $this->records;
        }

        if (!Schema::hasTable('module_settings')) {
            return $this->records = collect();
        }

        return $this->records = ModuleSetting::all()->keyBy('module_key');
    }
}
