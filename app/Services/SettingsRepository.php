<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Admin-editable settings (settings table), loaded once per request.
 */
class SettingsRepository
{
    /** @var array<string, string>|null */
    private ?array $values = null;

    /**
     * The stored value, or $default when the key is missing or blank.
     */
    public function get(string $key, string $default = ''): string
    {
        $value = $this->all()[$key] ?? '';

        return $value !== '' ? $value : $default;
    }

    public function enabled(string $key, bool $default = true): bool
    {
        return $this->get($key, $default ? '1' : '0') === '1';
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function save(array $values): void
    {
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                Setting::query()->updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
            }
        });

        $this->values = null;
    }

    /**
     * @return array<string, string>
     */
    private function all(): array
    {
        return $this->values ??= Setting::query()
            ->pluck('setting_value', 'setting_key')
            ->map(fn ($value) => (string) $value)
            ->all();
    }
}
