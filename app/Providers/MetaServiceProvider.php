<?php
// app/Providers/MetaServiceProvider.php

namespace App\Providers;

use App\Services\Meta\FakeMetaAdapter;
use App\Services\Meta\MetaAdapter;
use App\Services\Meta\RealMetaAdapter;
use Illuminate\Support\ServiceProvider;

class MetaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetaAdapter::class, function ($app) {
            $config = config('admin.meta');
            $adapter = $config['adapter'] ?? 'fake';

            return match ($adapter) {
                'real' => new RealMetaAdapter(
                    apiBase:     $config['real']['api_base'],
                    accessToken: config('agent.instagram.access_token'),
                    apiVersion:  config('agent.instagram.api_version'),
                ),
                default => new FakeMetaAdapter(
                    apiBase:    $config['fake']['api_base'],
                    apiVersion: config('agent.instagram.api_version'),
                ),
            };
        });
    }
}