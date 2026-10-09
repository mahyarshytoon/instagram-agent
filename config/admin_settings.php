<?php
// config/admin_settings.php

/**
 * نقشه‌ی تنظیمات پنل ادمین
 * 
 * ⚠️ این فایل فقط «نمایش» رو تعریف می‌کنه، نه «ذخیره‌سازی».
 * ⚠️ مقدار پیش‌فرض اینجا نیست — تو AdminSetting::getDefaultValues() هست.
 *    چون Laravel فایل‌های config رو به ترتیب الفبایی لود می‌کنه و
 *    admin_settings.php قبل از agent.php لود می‌شه.
 */

return [

    'groups' => [

        // ═══════════════════════════════════════════════════════
        // 🚫 گروه Spam
        // ═══════════════════════════════════════════════════════
        'spam' => [
            'label'       => '🚫 فیلتر اسپم',
            'description' => 'کلمات و الگوهای ممنوعه برای تشخیص اسپم',
            'color'       => 'red',
            'order'       => 1,
            'fields'      => [
                'spam.banned_words' => [
                    'label' => 'کلمات ممنوعه',
                    'type'  => 'array',
                    'help'  => 'هر خط یک کلمه. substring چک می‌شه (کلمه کوتاه، تبلیغات).',
                ],
                'spam.banned_patterns' => [
                    'label' => 'الگوهای Regex ممنوعه',
                    'type'  => 'array',
                    'help'  => 'هر خط یک regex. برای فحش‌ها و لینک‌ها.',
                ],
                'spam.min_length' => [
                    'label' => 'حداقل طول کامنت',
                    'type'  => 'int',
                    'help'  => 'کامنت‌های کوتاه‌تر اسپم حساب می‌شن.',
                ],
                'spam.max_length' => [
                    'label' => 'حداکثر طول کامنت',
                    'type'  => 'int',
                    'help'  => 'کامنت‌های بلندتر اسپم حساب می‌شن.',
                ],
            ],
        ],

        // ═══════════════════════════════════════════════════════
        // ⏱️ گروه Rate Limit
        // ═══════════════════════════════════════════════════════
        'rate_limit' => [
            'label'       => '⏱️ محدودیت نرخ',
            'description' => 'جلوگیری از پاسخ دادن به تعداد زیاد کامنت',
            'color'       => 'amber',
            'order'       => 2,
            'fields'      => [
                'rate_limit.max_per_minute' => [
                    'label' => 'حداکثر کامنت در دقیقه',
                    'type'  => 'int',
                    'help'  => 'در کل سیستم (همه‌ی کاربران).',
                ],
                'rate_limit.max_per_user_per_hour' => [
                    'label' => 'حداکثر کامنت هر کاربر در ساعت',
                    'type'  => 'int',
                    'help'  => 'برای جلوگیری از spam کردن یک کاربر.',
                ],
            ],
        ],

        // ═══════════════════════════════════════════════════════
        // 🤖 گروه AI
        // ═══════════════════════════════════════════════════════
        'ai' => [
            'label'       => '🤖 هوش مصنوعی',
            'description' => 'تنظیمات مدل و پرامپت',
            'color'       => 'purple',
            'order'       => 3,
            'fields'      => [
                'ai.model' => [
                    'label' => 'مدل',
                    'type'  => 'string',
                    'help'  => 'مثال: deepseek/deepseek-v4-flash-0731',
                ],
                'ai.temperature' => [
                    'label' => 'دمای مدل (0-2)',
                    'type'  => 'float',
                    'help'  => 'پایین = دقیق‌تر، بالا = خلاق‌تر.',
                ],
                'ai.max_tokens' => [
                    'label' => 'حداکثر توکن',
                    'type'  => 'int',
                    'help'  => 'طول حداکثر پاسخ.',
                ],
                'ai.system_prompt' => [
                    'label' => 'پرامپت سیستمی',
                    'type'  => 'text',
                    'help'  => 'دستور اصلی به مدل. اگه خالی باشه، از ProductSalesAgent استفاده می‌شه.',
                ],
            ],
        ],

        // ═══════════════════════════════════════════════════════
        // 🔌 گروه Meta
        // ═══════════════════════════════════════════════════════
        'meta' => [
            'label'       => '🔌 اتصال Meta',
            'description' => 'انتخاب آداپتور و توکن دسترسی',
            'color'       => 'indigo',
            'order'       => 4,
            'fields'      => [
                'meta.adapter' => [
                    'label'   => 'نوع آداپتور',
                    'type'    => 'select',
                    'options' => [
                        'fake' => '🧪 Fake (توسعه)',
                        'real' => '🌐 Real (production)',
                    ],
                    'help'    => 'تغییر نیاز به restart داره (Provider رو بخون).',
                ],
                'meta.access_token' => [
                    'label' => 'Access Token',
                    'type'  => 'secret',
                    'help'  => 'فقط برای adapter=real لازمه.',
                ],
            ],
        ],

    ],

];