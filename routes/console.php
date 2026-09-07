<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Kurs tengah BI harian (USD & SAR) — butuh `php artisan schedule:run` di cron server.
Schedule::command('kurs:fetch')->dailyAt('07:10');
