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
        $this->trustBundledCertificateAuthority();
    }

    /**
     * Windows PHP builds often ship without a CA bundle, so HTTPS calls fail
     * certificate verification. Use Mozilla's public bundle when it is present,
     * and leave verification enabled.
     */
    private function trustBundledCertificateAuthority(): void
    {
        $bundle = storage_path('certs/cacert.pem');

        if (is_file($bundle)) {
            Http::globalOptions([
                'verify' => $bundle,
            ]);
        }
    }
}
