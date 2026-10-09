<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
	   public function boot(): void
	{
		if ($proxy = env('AI_PROXY')) {
			Http::globalOptions([
				'proxy' => [
					'http'  => $proxy,
					'https' => $proxy,
					'no'    => ['127.0.0.1', 'localhost'],
				],
			]);
		}
	}
}