<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('Find your perfect PG today.');
})->purpose('Display an inspiring quote');

Schedule::command('rent:send-due-reminders --days=3')->dailyAt('10:00');
Schedule::command('rent:send-due-reminders --days=0')->dailyAt('10:00');
Schedule::command('rent:generate-monthly --due-day=5')->monthlyOn(1, '06:00');
Schedule::command('kyc:send-pending-reminders --days=2')->dailyAt('11:00');
Schedule::command('owner:send-pg-reminder --days=1')->dailyAt('12:00');
Schedule::command('leads:check-followups --stale-days=5')->hourly();
Schedule::command('complaints:check-escalated --days=3')->dailyAt('09:00');

// New WhatsApp template round — approved 2026-09-01
Schedule::command('properties:send-vacant-alerts --days=15')->dailyAt('11:30');
Schedule::command('agreements:send-renewal-reminders --days=15')->dailyAt('11:00');
Schedule::command('rent:send-overdue-owner-alerts --days=5')->dailyAt('10:30');
Schedule::command('admin:send-daily-summary')->dailyAt('21:00');
Schedule::command('owner:send-daily-lead-count')->dailyAt('21:30');
Schedule::command('visits:send-reminders --minutes=30')->everyFifteenMinutes();