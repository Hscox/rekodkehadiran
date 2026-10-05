<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:auto-clock-out', function () {
    AttendanceController::autoClockOutForgottenSessions();
    $this->info('Auto clock out executed successfully.');
})->purpose('Automatically clock out active sessions at 6pm');

Schedule::call(function () {
    AttendanceController::autoClockOutForgottenSessions();
})->dailyAt('18:00');
