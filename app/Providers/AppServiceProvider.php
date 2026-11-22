<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Indonesian month and day names (APP_LOCALE=id).
        Carbon::setLocale(config('app.locale'));

        // @rupiah($angka) -> "Rp 25.000"
        Blade::directive('rupiah', fn (string $expression) => "<?php echo 'Rp '.number_format((int) ($expression), 0, ',', '.'); ?>");
    }
}
