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
// Owners whose Pizi Owner app is out of date get a WhatsApp reminder each day until they update (admin switches it on)
Schedule::command('owner-app:send-update-reminders')->dailyAt('12:30');
// Owners who never signed in to the owner app get a daily "download it from the Play Store" WhatsApp (admin switches it on)
Schedule::command('owner-app:send-install-reminders')->dailyAt('12:45');
// Bed Plan: renew months that have ended (or end the plan when the Bed Wallet cannot pay)
Schedule::command('bed-plan:renew')->hourly()->withoutOverlapping();

// New WhatsApp template round — approved 2026-09-01
Schedule::command('properties:send-vacant-alerts --days=15')->dailyAt('11:30');
Schedule::command('agreements:send-renewal-reminders --days=15')->dailyAt('11:00');
Schedule::command('rent:send-overdue-owner-alerts --days=5')->dailyAt('10:30');
Schedule::command('admin:send-daily-summary')->dailyAt('21:00');
Schedule::command('owner:send-daily-lead-count')->dailyAt('11:30');
Schedule::command('visits:send-reminders --minutes=30')->everyFifteenMinutes();

// Owner app notifications (inbox + push)
Schedule::command('owner:notify-moveouts --days=3')->dailyAt('09:30');
Schedule::command('owner:notify-leads-waiting --days=3')->dailyAt('12:30');
Schedule::command('owner:send-monthly-summary')->monthlyOn(1, '09:00');