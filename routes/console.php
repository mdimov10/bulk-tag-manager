<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote')->hourly();
//
//Schedule::command('telescope:prune')->dailyAt('03:00');

Schedule::command('app:refresh-store-expires')->dailyAt('02:30');

