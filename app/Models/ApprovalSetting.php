<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ApprovalSetting extends Model
{
    protected $fillable = [
        'module_key',
        'label',
        'min_amount',
        'is_active',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'is_active'  => 'boolean',
        ];
    }

    public static function getMinAmountForModule(string $moduleKey): float
    {
        $cacheKey = "approval_min_amount:{$moduleKey}";

        return (float) Cache::remember($cacheKey, now()->addMinutes(30), function () use ($moduleKey) {
            $setting = static::query()
                ->where('module_key', $moduleKey)
                ->where('is_active', true)
                ->first();

            return $setting ? (float) $setting->min_amount : 0.0;
        });
    }

    public static function clearModuleCache(string $moduleKey): void
    {
        Cache::forget("approval_min_amount:{$moduleKey}");
    }
}
