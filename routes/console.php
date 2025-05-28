<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\RunSpinningAlgorithm;


Schedule::command(RunSpinningAlgorithm::class)
    -> everyMinute(); // ->everyMinute();  or ->everySecond() if using Laravel Octane

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
