<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Local dev: fix SSL certificate for Razorpay / external API cURL calls.
        // XAMPP ka default curl-ca-bundle.crt outdated hota hai — cURL error 60 aata hai.
        // cacert.pem ko storage/app/ mein download karo:
        //   Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "storage/app/cacert.pem"
        if (app()->environment('local')) {
            // Priority 1: project ke andar ka cert (recommended)
            $projectCert = storage_path('app/cacert.pem');
            // Priority 2: XAMPP ka cert (agar download kiya ho)
            $xamppCert   = 'C:/xampp/apache/bin/curl-ca-bundle.crt';

            $caBundle = file_exists($projectCert) ? $projectCert : (file_exists($xamppCert) ? $xamppCert : null);

            if ($caBundle) {
                ini_set('curl.cainfo', $caBundle);
                ini_set('openssl.cafile', $caBundle);
            }
        }
    }
}
