```php
<?php

use App\Enums\PriceInterval;
use App\Enums\SubscriptionStatus;
use App\Exceptions\BusinessRuleException;
use Carbon\Carbon;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Services\SubscriptionLifecycleService;

function service(): SubscriptionLifecycleService
{
    return new SubscriptionLifecycleService();
}


/*
|--------------------------------------------------------------------------
| calculateEndDate()
|--------------------------------------------------------------------------
*/

it('calculates end date correctly for daily interval', function () {
    $startDate = Carbon::parse('2026-09-07 10:00:00');

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::DAY,
    ]);

    $result = service()->calculateEndDate($startDate, $price);

    expect($result->equalTo(
        Carbon::parse('2026-09-08 10:00:00')
    ))->toBeTrue();

    // Make sure the original date was not modified.
    expect($startDate->equalTo(
        Carbon::parse('2026-09-07 10:00:00')
    ))->toBeTrue();
});


it('calculates end date correctly for weekly interval', function () {
    $startDate = Carbon::parse('2026-09-07 10:00:00');

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::WEEK,
    ]);

    $result = service()->calculateEndDate($startDate, $price);

    expect($result->equalTo(
        Carbon::parse('2026-09-14 10:00:00')
    ))->toBeTrue();
});


it('calculates end date correctly for monthly interval', function () {
    $startDate = Carbon::parse('2026-09-07 10:00:00');

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    $result = service()->calculateEndDate($startDate, $price);

    expect($result->equalTo(
        Carbon::parse('2026-10-07 10:00:00')
    ))->toBeTrue();
});


it('calculates end date correctly for yearly interval', function () {
    $startDate = Carbon::parse('2026-09-07 10:00:00');

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::YEAR,
    ]);

    $result = service()->calculateEndDate($startDate, $price);

    expect($result->equalTo(
        Carbon::parse('2027-09-07 10:00:00')
    ))->toBeTrue();
});


/*
|--------------------------------------------------------------------------
| prepareSubscriptionStatus()
|--------------------------------------------------------------------------
*/


it('allows pending subscription to become active', function () {
    Carbon::setTestNow('2026-09-07 10:00:00');

    $subscription = new Subscription([
        'status' => SubscriptionStatus::PENDING,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    $result = service()->prepareSubscriptionStatus(
        [
            'status' => SubscriptionStatus::ACTIVE,
        ],
        $price,
        $subscription
    );

    expect($result['status'])
        ->toBe(SubscriptionStatus::ACTIVE);

    expect($result['start_date']->equalTo(
        Carbon::parse('2026-09-07 10:00:00')
    ))->toBeTrue();

    expect($result['end_date']->equalTo(
        Carbon::parse('2026-10-07 10:00:00')
    ))->toBeTrue();

    expect($result['trial_end_date'])
        ->toBeNull();

    Carbon::setTestNow();
});




it('does not allow active subscription to return to pending', function () {
    $subscription = new Subscription([
        'status' => SubscriptionStatus::ACTIVE,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    expect(fn() => service()->prepareSubscriptionStatus(
        [
            'status' => SubscriptionStatus::PENDING,
        ],
        $price,
        $subscription
    ))->toThrow(
        BusinessRuleException::class,
        'Cannot return active subscription to pending.'
    );
});


it('does not allow canceled subscription to become active', function () {
    $subscription = new Subscription([
        'status' => SubscriptionStatus::CANCELED,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    expect(fn() => service()->prepareSubscriptionStatus(
        [
            'status' => SubscriptionStatus::ACTIVE,
        ],
        $price,
        $subscription
    ))->toThrow(
        BusinessRuleException::class,
        'Cannot activate canceled subscription.'
    );
});


it('does not allow trial expired subscription to become active', function () {
    $subscription = new Subscription([
        'status' => SubscriptionStatus::TRAIL_EXPIRED,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    expect(fn() => service()->prepareSubscriptionStatus(
        [
            'status' => SubscriptionStatus::ACTIVE,
        ],
        $price,
        $subscription
    ))->toThrow(
        BusinessRuleException::class,
        'Trial expired subscription cannot be activated. Create a new subscription.'
    );
});


it('allows pending subscription to be canceled', function () {
    Carbon::setTestNow('2026-09-07 10:00:00');

    $subscription = new Subscription([
        'status' => SubscriptionStatus::PENDING,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    $result = service()->prepareSubscriptionStatus(
        [
            'status' => SubscriptionStatus::CANCELED,
        ],
        $price,
        $subscription
    );

    expect($result['status'])
        ->toBe(SubscriptionStatus::CANCELED);

    expect($result['canceled_at']->equalTo(
        Carbon::parse('2026-09-07 10:00:00')
    ))->toBeTrue();

    Carbon::setTestNow();
});


it('allows active subscription to be canceled', function () {
    Carbon::setTestNow('2026-09-07 10:00:00');

    $subscription = new Subscription([
        'status' => SubscriptionStatus::ACTIVE,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    $result = service()->prepareSubscriptionStatus(
        [
            'status' => SubscriptionStatus::CANCELED,
        ],
        $price,
        $subscription
    );

    expect($result['status'])
        ->toBe(SubscriptionStatus::CANCELED);

    expect($result['canceled_at']->equalTo(
        Carbon::parse('2026-09-07 10:00:00')
    ))->toBeTrue();

    Carbon::setTestNow();
});


it('allows expired subscription to be canceled without setting canceled_at', function () {
    $subscription = new Subscription([
        'status' => SubscriptionStatus::EXPIRED,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    $result = service()->prepareSubscriptionStatus(
        [
            'status' => SubscriptionStatus::CANCELED,
        ],
        $price,
        $subscription
    );

    expect($result['status'])
        ->toBe(SubscriptionStatus::CANCELED);

    expect($result)
        ->not->toHaveKey('canceled_at');
});


it('does not allow non pending subscription to become active', function () {
    $subscription = new Subscription([
        'status' => SubscriptionStatus::EXPIRED,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    expect(fn() => service()->prepareSubscriptionStatus(
        [
            'status' => SubscriptionStatus::ACTIVE,
        ],
        $price,
        $subscription
    ))->toThrow(
        BusinessRuleException::class,
        'Only pending subscriptions can be activated.'
    );
});


it('keeps the existing status when no status is provided', function () {
    $subscription = new Subscription([
        'status' => SubscriptionStatus::PENDING,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    $result = service()->prepareSubscriptionStatus(
        [],
        $price,
        $subscription
    );

    expect($result['status'])
        ->toBe(SubscriptionStatus::PENDING);
});


it('accepts string status values', function () {
    Carbon::setTestNow('2026-09-07 10:00:00');

    $subscription = new Subscription([
        'status' => SubscriptionStatus::PENDING,
    ]);

    $price = new SubscriptionPrice([
        'interval' => PriceInterval::MONTH,
    ]);

    $result = service()->prepareSubscriptionStatus(
        [
            'status' => SubscriptionStatus::ACTIVE->value,
        ],
        $price,
        $subscription
    );

    expect($result['status'])
        ->toBe(SubscriptionStatus::ACTIVE);

    expect($result['start_date']->equalTo(
        Carbon::parse('2026-09-07 10:00:00')
    ))->toBeTrue();

    expect($result['end_date']->equalTo(
        Carbon::parse('2026-10-07 10:00:00')
    ))->toBeTrue();

    Carbon::setTestNow();
});


/*
|--------------------------------------------------------------------------
| prepareTrialData()
|--------------------------------------------------------------------------
*/

it('sets trial end date for pending subscription with trial days', function () {
    Carbon::setTestNow('2026-09-07 10:00:00');

    $price = new SubscriptionPrice([
        'trial_days' => 14,
    ]);

    $result = service()->prepareTrialData(
        [
            'status' => SubscriptionStatus::PENDING,
        ],
        $price
    );

    expect($result['trial_end_date']->equalTo(
        Carbon::parse('2026-09-21 10:00:00')
    ))->toBeTrue();

    Carbon::setTestNow();
});


it('does not set trial end date when trial days are zero', function () {
    $price = new SubscriptionPrice([
        'trial_days' => 0,
    ]);

    $result = service()->prepareTrialData(
        [
            'status' => SubscriptionStatus::PENDING,
        ],
        $price
    );

    expect($result)
        ->not->toHaveKey('trial_end_date');
});


it('does not set trial end date for active subscription', function () {
    $price = new SubscriptionPrice([
        'trial_days' => 14,
    ]);

    $result = service()->prepareTrialData(
        [
            'status' => SubscriptionStatus::ACTIVE,
        ],
        $price
    );

    expect($result)
        ->not->toHaveKey('trial_end_date');
});


it('accepts string pending status when preparing trial data', function () {
    Carbon::setTestNow('2026-09-07 10:00:00');

    $price = new SubscriptionPrice([
        'trial_days' => 7,
    ]);

    $result = service()->prepareTrialData(
        [
            'status' => SubscriptionStatus::PENDING->value,
        ],
        $price
    );

    expect($result['trial_end_date']->equalTo(
        Carbon::parse('2026-09-14 10:00:00')
    ))->toBeTrue();

    Carbon::setTestNow();
});
