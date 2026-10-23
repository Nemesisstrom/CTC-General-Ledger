<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dijalankan otomatis setiap tanggal 1 jam 01:00 WIB
Schedule::command('app:process-recurring-expenses')->monthlyOn(1, '01:00');
