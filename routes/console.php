<?php

use App\Services\Memberships;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('bvcc:expire-memberships', function (Memberships $memberships) {
    $this->info($memberships->expireLapsed().' memberships marked as expired.');
})->purpose('Mark memberships whose year has ended as expired');

// Needs the scheduler running in production: see docs/spec.md.
Schedule::command('bvcc:expire-memberships')->dailyAt('02:00');
