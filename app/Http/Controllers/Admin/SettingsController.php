<?php
// app/Http/Controllers/Admin/SettingsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * نمایش صفحه‌ی تنظیمات (همه tab ها)
     * 
     * URL: GET /admin/settings
     * URL: GET /admin/settings?tab=spam
     */
    public function index(Request $request): View
    {
        // ═══ ۱. تعریف گروه‌ها (مرتب‌شده بر اساس order) ═══
        $groups = collect(config('admin_settings.groups', []))
            ->sortBy('order')
            ->toArray();

        // ═══ ۲. تب فعال (پیش‌فرض: اولین گروه) ═══
        $activeTab = $request->input('tab', array_key_first($groups));

        // اگه تب نامعتبر بود، برگرد به اولین
        if (! isset($groups[$activeTab])) {
            $activeTab = array_key_first($groups);
        }

        // ═══ ۳. گرفتن مقادیر فعلی از DB ═══
        $values = [];
        foreach ($groups as $groupKey => $groupData) {
            foreach ($groupData['fields'] ?? [] as $key => $field) {
                $values[$key] = AdminSetting::get($key);
            }
        }

        return view('admin.settings.index', compact('groups', 'activeTab', 'values'));
    }

    /**
     * ذخیره‌ی تغییرات یه تب
     * 
     * URL: POST /admin/settings
     */
    public function update(Request $request): RedirectResponse
    {
        // ═══ ۱. تعیین گروه ═══
        $groupKey = $request->input('_group');
        $groups = config('admin_settings.groups', []);

        if (! isset($groups[$groupKey])) {
            return back()->with('error', 'گروه نامعتبر.');
        }

        $groupData = $groups[$groupKey];
        $saved = 0;

        // ═══ ۲. پردازش هر فیلد ═══
        foreach ($groupData['fields'] ?? [] as $key => $field) {
            $inputName = $this->inputName($key);

            // فیلدهای secret اگه خالی بودن، مقدار قبلی حفظ بشه
            if (($field['type'] ?? 'string') === 'secret' && ! $request->filled($inputName)) {
                continue;
            }

            // اگه فیلد تو request نبود، رد کن
            if (! $request->has($inputName)) {
                continue;
            }

            $raw = $request->input($inputName);
            $value = $this->castInput($raw, $field['type'] ?? 'string');

            AdminSetting::set($key, $value, $groupKey);
            $saved++;
        }

        Log::info('Admin settings updated', [
            'group' => $groupKey,
            'count' => $saved,
            'ip'    => $request->ip(),
        ]);

        return redirect()
            ->route('admin.settings.index', ['tab' => $groupKey])
            ->with('success', "✅ {$saved} تنظیم با موفقیت ذخیره شد.");
    }

    // ═══════════════════════════════════════════════════════════
    // 🔧 کمکی‌ها
    // ═══════════════════════════════════════════════════════════

    /**
     * تبدیل key به input name
     * 
     * چرا؟ چون HTML input با نقطه کار نمی‌کنه تو PHP.
     * PHP نقطه رو تو name به _ تبدیل می‌کنه.
     * پس 'spam.banned_words' → 'spam_banned_words'
     */
    private function inputName(string $key): string
    {
        return str_replace('.', '_', $key);
    }

    /**
     * تبدیل input خام به نوع درست
     */
    private function castInput(mixed $raw, string $type): mixed
    {
        return match ($type) {
            'int'    => (int) $raw,
            'float'  => (float) $raw,
            'bool'   => (bool) $raw,
            'array'  => $this->parseArrayInput($raw),
            'secret' => (string) $raw,
            'text'   => (string) $raw,
            'select' => (string) $raw,
            default  => (string) $raw,
        };
    }

    /**
     * تبدیل textarea (هر خط یه آیتم) به array
     */
    private function parseArrayInput(mixed $raw): array
    {
        if (is_array($raw)) {
            $raw = implode("\n", $raw);
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);

        return array_values(array_filter(
            array_map('trim', $lines),
            fn ($line) => $line !== ''
        ));
    }
}