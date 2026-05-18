<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tenants:suspend-expired')->dailyAt('01:15');
Schedule::command('platform:purge-compliance-retention')->dailyAt('02:00');
Schedule::command('platform:suspend-payment-delinquent')->dailyAt('03:00');
