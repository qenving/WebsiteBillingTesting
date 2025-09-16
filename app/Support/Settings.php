<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting:$key", 300, function () use ($key, $default) {
            $item = Setting::where('key', $key)->first();
            if (! $item) return $default;
            return static::cast($item->value, $item->type ?? 'string');
        });
    }

    public static function set(string $key, $value, string $type = 'string', ?string $group = null): void
    {
        Cache::forget("setting:$key");
        Setting::updateOrCreate(['key' => $key], [
            'value' => is_scalar($value) ? (string) $value : json_encode($value),
            'type' => $type,
            'group' => $group,
        ]);
    }

    protected static function cast(?string $value, string $type)
    {
        if ($value === null) return null;
        switch ($type) {
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
}
