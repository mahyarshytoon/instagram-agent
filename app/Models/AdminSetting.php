<?php
// app/Models/AdminSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdminSetting extends Model
{
    protected $table = 'admin_settings';
    protected $fillable = ['key', 'value', 'group', 'description', 'type'];

    private const CACHE_TTL = 300;
    private const CACHE_PREFIX = 'admin_settings:';
    private const NULL_SENTINEL = '__ADMIN_SETTING_NULL__';

    // ═══════════════════════════════════════════════════════════
    // 📖 READ
    // ═══════════════════════════════════════════════════════════

    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        $cached = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            $setting = self::where('key', $key)->first();

            if (! $setting) {
                return self::NULL_SENTINEL;
            }

            return [
                'value' => $setting->value,
                'type'  => $setting->type,
            ];
        });

        if ($cached === self::NULL_SENTINEL) {
            return $default;
        }

        return self::castValue($cached['value'], $cached['type']);
    }

    public static function group(string $group): array
    {
        $prefix = $group . '.';
        $prefixLen = strlen($prefix);

        return self::where('group', $group)
            ->get()
            ->mapWithKeys(function ($s) use ($prefix, $prefixLen) {
                $shortKey = str_starts_with($s->key, $prefix)
                    ? substr($s->key, $prefixLen)
                    : $s->key;

                return [$shortKey => self::castValue($s->value, $s->type)];
            })
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════
    // 🎯 DEFAULT VALUES — مقادیر پیش‌فرض
    // ═══════════════════════════════════════════════════════════

    /**
     * نگاشتِ key → مقدار پیش‌فرض
     * 
     * اینجا مقدار پیش‌فرض رو از config('agent...') می‌گیریم،
     * نه تو config/admin_settings.php.
     * چون Laravel admin_settings.php رو قبل از agent.php لود می‌کنه.
     * 
     * @return array<string, mixed>
     */
    public static function getDefaultValues(): array
    {
        return [
            // ── Spam ──
            'spam.banned_words'    => config('agent.spam.banned_words', []),
            'spam.banned_patterns' => config('agent.spam.banned_patterns', []),
            'spam.min_length'      => config('agent.spam.min_length', 2),
            'spam.max_length'      => config('agent.spam.max_length', 500),

            // ── Rate Limit ──
            'rate_limit.max_per_minute'        => config('agent.rate_limit.max_per_minute', 10),
            'rate_limit.max_per_user_per_hour' => config('agent.rate_limit.max_per_user_per_hour', 5),

            // ── AI ──
            'ai.model'       => config('agent.ai.model', 'deepseek/deepseek-v4-flash-0731'),
            'ai.temperature' => config('agent.ai.temperature', 0.7),
            'ai.max_tokens'  => config('agent.ai.max_tokens', 1000),
            'ai.system_prompt' => '',

            // ── Meta ──
            'meta.adapter'      => config('admin.meta.adapter', 'fake'),
            'meta.access_token' => config('agent.instagram.access_token', ''),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // ✏️ WRITE
    // ═══════════════════════════════════════════════════════════

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $encoded = self::encodeValue($value);

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $encoded['value'],
                'type'  => $encoded['type'],
                'group' => $group,
            ]
        );

        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * پر کردن DB با مقادیر پیش‌فرض
     * فقط کلیدهایی که تو DB نیستن ساخته می‌شن.
     */
    public static function seedDefaults(?string $onlyGroup = null): int
    {
        $groups = config('admin_settings.groups', []);
        $defaults = self::getDefaultValues();

        $existingKeys = self::pluck('key')->flip()->all();
        $count = 0;

        foreach ($groups as $groupKey => $groupData) {
            if ($onlyGroup !== null && $groupKey !== $onlyGroup) {
                continue;
            }

            foreach ($groupData['fields'] ?? [] as $key => $field) {
                if (isset($existingKeys[$key])) {
                    continue;
                }

                self::set(
                    $key,
                    $defaults[$key] ?? null,
                    $groupKey
                );

                $count++;
            }
        }

        return $count;
    }

    /**
     * آپدیت مقادیر از getDefaultValues()
     * (کلیدهای موجود رو هم آپدیت می‌کنه)
     */
    public static function syncFromConfig(?string $onlyGroup = null, array $onlyKeys = []): int
    {
        $groups = config('admin_settings.groups', []);
        $defaults = self::getDefaultValues();
        $count = 0;

        foreach ($groups as $groupKey => $groupData) {
            if ($onlyGroup !== null && $groupKey !== $onlyGroup) {
                continue;
            }

            foreach ($groupData['fields'] ?? [] as $key => $field) {
                if (! empty($onlyKeys) && ! in_array($key, $onlyKeys, true)) {
                    continue;
                }

                self::set($key, $defaults[$key] ?? null, $groupKey);
                $count++;
            }
        }

        return $count;
    }

    // ═══════════════════════════════════════════════════════════
    // 🔄 CAST
    // ═══════════════════════════════════════════════════════════

    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int'    => (int) $value,
            'float'  => (float) $value,
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array'  => json_decode($value, true) ?? [],
            default  => $value,
        };
    }

    private static function encodeValue(mixed $value): array
    {
        $type = match (true) {
            is_int($value)   => 'int',
            is_float($value) => 'float',
            is_bool($value)  => 'bool',
            is_array($value) => 'array',
            default          => 'string',
        };

        $encoded = match ($type) {
            'array' => json_encode($value, JSON_UNESCAPED_UNICODE),
            'bool'  => $value ? '1' : '0',
            default => (string) $value,
        };

        return ['value' => $encoded, 'type' => $type];
    }
}