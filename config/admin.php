<?php
// config/admin.php

return [

    /*
    |--------------------------------------------------------------------------
    | احراز هویت ادمین
    |--------------------------------------------------------------------------
    */

    'auth' => [
    // اول base64 رو چک کن، اگه نبود، hash خام
			'password_hash' => (function () {
				$b64 = env('ADMIN_PASSWORD_HASH_BASE64');
				if ($b64) {
					return base64_decode($b64);
				}
				return env('ADMIN_PASSWORD_HASH');
			})(),

			'session_key'     => 'admin_authenticated_at',
			'session_ttl'     => (int) env('ADMIN_SESSION_TTL', 120),
			'max_attempts'    => (int) env('ADMIN_MAX_ATTEMPTS', 5),
			'lockout_minutes' => (int) env('ADMIN_LOCKOUT_MINUTES', 15),
		],

       

    /*
    |--------------------------------------------------------------------------
    | مسیرهای پنل
    |--------------------------------------------------------------------------
    */

    'route' => [
        'prefix'     => env('ADMIN_ROUTE_PREFIX', 'admin'),
        'login_path' => 'login',
        'home_path'  => 'dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */

    'ui' => [
        'title'       => env('ADMIN_TITLE', 'پنل مدیریت Instagram Agent'),
        'per_page'    => (int) env('ADMIN_PER_PAGE', 20),
        'date_format' => 'Y/m/d H:i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta Adapter (Fake vs Real)
    |--------------------------------------------------------------------------
    */

    'meta' => [
        // "fake" = FakeMetaServer (dev) | "real" = Meta Graph API (production)
        'adapter' => env('META_ADAPTER', 'fake'),

        'real' => [
            'api_base' => env('INSTAGRAM_API_BASE', 'https://graph.facebook.com'),
        ],

        'fake' => [
            'api_base' => env('FAKE_META_BASE', 'http://127.0.0.1:8001/api/fake-meta'),
        ],
    ],

];