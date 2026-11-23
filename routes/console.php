<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Otomatis memproses beban berulang setiap hari pukul 01:00
Schedule::command('expenses:process-recurring')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/recurring-expenses.log'));

// Bersihkan failed jobs lama setiap minggu agar tabel jobs tetap rapi
Schedule::command('queue:prune-failed --hours=168')
    ->weekly();
