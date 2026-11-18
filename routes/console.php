<?php

use App\Services\PostReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Feature 10: send post reminders that are due.
Artisan::command('posts:remind', function (PostReminderService $reminders) {
    $sent = $reminders->sendDue();
    $this->info("Sent {$sent} post reminder(s).");
})->purpose('Send reminders for scheduled posts');

Schedule::command('posts:remind')->everyMinute();
