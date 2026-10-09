<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Instagram API Settings
    |--------------------------------------------------------------------------
    */

    'instagram' => [
        'app_id'        => env('INSTAGRAM_APP_ID'),
        'app_secret'    => env('INSTAGRAM_APP_SECRET'),
        'verify_token'  => env('INSTAGRAM_VERIFY_TOKEN'),
        'access_token'  => env('INSTAGRAM_ACCESS_TOKEN'),
        'api_version'   => env('INSTAGRAM_API_VERSION', 'v21.0'),
        'api_base'      => env('INSTAGRAM_API_BASE', 'https://graph.facebook.com'),
        'timeout'       => (int) env('INSTAGRAM_TIMEOUT', 15),

        'retry' => [
            'times'    => (int) env('INSTAGRAM_RETRY_TIMES', 2),
            'sleep_ms' => (int) env('INSTAGRAM_RETRY_SLEEP_MS', 500),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'max_per_minute'         => env('AGENT_MAX_COMMENTS_PER_MINUTE', 10),
        'max_per_user_per_hour'  => env('AGENT_MAX_COMMENTS_PER_USER_PER_HOUR', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Spam Filter
    |--------------------------------------------------------------------------
    */

    'spam' => [
        'min_length' => env('AGENT_MIN_COMMENT_LENGTH', 2),
        'max_length' => env('AGENT_MAX_COMMENT_LENGTH', 500),

        /*
        |----------------------------------------------------------------------
        | کلمات ممنوعه (substring — برای تبلیغات و عبارت‌های بلند)
        |----------------------------------------------------------------------
        */
        'banned_words' => [
            // انگلیسی — تبلیغات
            'buy followers',
            'free followers',
            'click here',
            'make money',
            'casino',
            'crypto pump',

            // فارسی — تبلیغات
            'فالوور رایگان',
            'فالور رایگان',
            'دایرکت بده',
            'دایرکت بدید',
            'به پیج من بیا',
            'به پیج من بیاید',
            'به پیج ما بیا',
            'به پیج ما بیاید',
            'کلیک کنید',
            'لینک بایو',
            'لینک بایو ما',
            'نمایندگی فروش',
            'کد تخفیف من',
            'کد معرفی',
            'افزایش فالوور',
            'خرید فالوور',
        ],

        /*
        |----------------------------------------------------------------------
        | الگوهای ممنوعه (regex — دقیق‌تر، برای فحش‌ها و لینک)
        |----------------------------------------------------------------------
        */
        'banned_patterns' => [
            // فحش‌های کوتاه — با جداکننده صریح (چون \b تو فارسی کار نمی‌کنه)
            '/(?:^|[\s،,\.!؟\?!:;])(خر|خری|خره|گاو|سگ|کیر|کص|کوس|کون|جاکش|جنده|قحبه|لاشی|احمق|نفهم|دیوونه|گاگول)(?:$|[\s،,\.!؟\?!:;])/u',

            // فحش‌های بلند (بدون نیاز به boundary — substring می‌گیره)
            '/بیشرف|بیشعور|حرومزاده|حروم زاده|پدرسگ|مادرجنده|مادر قحبه|مادرقحبه|کثافت|کثافتی/u',

            // ترکیبی — ناموس + فحش
            '/(مادر|پدر|خواهر|ناموس)\s*(جنده|قحبه|کص|کیر|کون|گایید)/u',

            // گاییدن (همه صرف‌ها)
            '/گایید|گاییدم|گاییدن|گاییدنت|گاییده/u',

            // لینک
            '/https?:\/\/\S+/u',
            '/www\.\S+/u',

            // منشن (@username)
            '/@[a-zA-Z0-9_.]+/u',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Settings
    |--------------------------------------------------------------------------
    */

    'ai' => [
        'provider'    => env('AI_PROVIDER', 'openai-compatible'),
        'api_base'    => env('OPENAI_COMPATIBLE_URL', 'https://openrouter.ai/api/v1'),
        'api_key'     => env('OPENAI_COMPATIBLE_API_KEY'),
        'model'       => env('AI_MODEL', 'deepseek/deepseek-v4-flash-0731'),
        'max_tokens'  => (int) env('AI_MAX_TOKENS', 1000),
        'temperature' => (float) env('AI_TEMPERATURE', 0.7),
        'timeout'     => (int) env('AI_TIMEOUT', 30),
        'proxy'       => env('AI_PROXY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversation
    |--------------------------------------------------------------------------
    */

    'conversation' => [
        'merge_window_minutes' => 30,
        'max_messages'         => 20,
    ],

];