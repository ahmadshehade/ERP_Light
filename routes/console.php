<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('subscriptions:change-to-expired')
    ->dailyAt('00:00');

Schedule::command('subscriptions:delete-end')
    ->dailyAt('00:10');

Schedule::command('subscriptions:delete-canceled')
    ->dailyAt('00:20');

Schedule::command('users:cleanup')
    ->weeklyOn(5, '01:00');

Schedule::command('subscription-plans:cleanup')
    ->weeklyOn(5, '01:10');

Schedule::command('subscription-price:cleanup')
    ->weeklyOn(5, '01:20');

Schedule::command('subscriptions:cleanup')
    ->weeklyOn(5, '01:30');

Schedule::command('subscription:notification-expiring')
    ->dailyAt('09.00');
