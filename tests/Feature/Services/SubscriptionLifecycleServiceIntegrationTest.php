<?php

use App\Enums\PriceInterval;
use App\Enums\SubscriptionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Modules\Central\Models\Company;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Services\SubscriptionLifecycleService;

uses(DatabaseTransactions::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function createLifecycleUser(): User
{
    return User::create([
        'name' => 'Lifecycle Owner',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);
}

function createLifecycleCompany(User $owner): Company
{
    return Company::withoutEvents(function () use ($owner) {

        $company = new Company([
            'name' => [
                'en' => 'Company ' . fake()->unique()->numberBetween(1000, 9999),
                'ar' => 'شركة ' . fake()->unique()->numberBetween(1000, 9999),
            ],
            'subdomain' => 'company-' . fake()->unique()->numberBetween(100000, 999999),
            'max_users' => 10,
            'is_active' => true,
        ]);

        $company->forceFill([
            'owner_id' => $owner->id,
        ]);

        $company->save();

        return $company;
    });
}

function createLifecyclePlan(bool $active = true): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => [
            'en' => 'Plan ' . fake()->unique()->numberBetween(1000, 9999),
            'ar' => 'خطة ' . fake()->unique()->numberBetween(1000, 9999),
        ],
        'description' => [
            'en' => 'Test subscription plan',
            'ar' => 'خطة اشتراك للاختبار',
        ],
        'is_active' => $active,
    ]);
}

function createLifecyclePrice(
    ?SubscriptionPlan $plan = null,
    bool $active = true
): SubscriptionPrice {
    $plan ??= createLifecyclePlan();

    return SubscriptionPrice::create([
        'plan_id' => $plan->id,
        'price' => 99.99,
        'interval' => PriceInterval::MONTH->value,
        'stripe_price_id' => null,
        'is_active' => $active,
        'has_trial' => false,
        'trial_days' => 0,
    ]);
}

function createLifecycleSubscription(
    Company $company,
    SubscriptionPrice $price,
    SubscriptionStatus $status
): Subscription {
    return Subscription::create([
        'company_id' => $company->id,
        'price_id' => $price->id,
        'status' => $status->value,
        'start_date' => now(),
        'end_date' => now()->addMonth(),
        'trial_end_date' => null,
        'canceled_at' => null,
    ]);
}

/*
|--------------------------------------------------------------------------
| prepareSubscriptionData()
|--------------------------------------------------------------------------
*/

it('prepares valid subscription data', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $this->actingAs($owner);

    $data = [
        'company_id' => $company->id,
        'price_id' => $price->id,
    ];

    $result = $service->prepareSubscriptionData($data);

    expect($result)
        ->toHaveKey('company_id', $company->id)
        ->toHaveKey('price_id', $price->id);
});


it('rejects inactive company in prepare subscription data', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $company->update([
        'is_active' => false,
    ]);

    $this->actingAs($owner);

    expect(fn() => $service->prepareSubscriptionData([
        'company_id' => $company->id,
    ]))
        ->toThrow(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );
});


it('rejects company that does not belong to authenticated user', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $anotherOwner = createLifecycleUser();

    $company = createLifecycleCompany($anotherOwner);

    $this->actingAs($owner);

    expect(fn() => $service->prepareSubscriptionData([
        'company_id' => $company->id,
    ]))
        ->toThrow(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );
});


it('rejects nonexistent company', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $this->actingAs($owner);

    expect(fn() => $service->prepareSubscriptionData([
        'company_id' => fake()->uuid(),
    ]))
        ->toThrow(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );
});


it('rejects inactive price in prepare subscription data', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $price = createLifecyclePrice(
        active: false
    );

    $this->actingAs($owner);

    expect(fn() => $service->prepareSubscriptionData([
        'price_id' => $price->id,
    ]))
        ->toThrow(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );
});


it('rejects nonexistent price in prepare subscription data', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $this->actingAs($owner);

    expect(fn() => $service->prepareSubscriptionData([
        'price_id' => 999999,
    ]))
        ->toThrow(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );
});


it('keeps subscription data unchanged when company and price are absent', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $this->actingAs($owner);

    $data = [
        'status' => SubscriptionStatus::PENDING->value,
    ];

    expect(
        $service->prepareSubscriptionData($data)
    )->toBe($data);
});


/*
|--------------------------------------------------------------------------
| refreshStatus()
|--------------------------------------------------------------------------
*/

it('changes pending subscription to trial expired after trial ends', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $subscription->update([
        'trial_end_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
    ]);

    $service->refreshStatus($subscription);

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::TRAIL_EXPIRED);
});


it('changes subscription to expired after end date passes', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $subscription->update([
        'trial_end_date' => null,
        'end_date' => now()->subDay(),
    ]);

    $service->refreshStatus($subscription);

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::EXPIRED);
});


it('keeps pending subscription when trial is still active', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    $subscription->update([
        'trial_end_date' => now()->addDay(),
        'end_date' => now()->addMonth(),
    ]);

    $service->refreshStatus($subscription);

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::PENDING);
});


it('keeps active subscription when end date is still valid', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $subscription->update([
        'trial_end_date' => null,
        'end_date' => now()->addDay(),
    ]);

    $service->refreshStatus($subscription);

    $subscription->refresh();

    expect($subscription->status)
        ->toBe(SubscriptionStatus::ACTIVE);
});


/*
|--------------------------------------------------------------------------
| renew()
|--------------------------------------------------------------------------
*/

it('renews an active subscription', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $newSubscription = $service->renew(
        $subscription,
        [
            'price_id' => $price->id,
        ]
    );

    expect($newSubscription)
        ->toBeInstanceOf(Subscription::class)
        ->status->toBe(SubscriptionStatus::PENDING);

    expect($newSubscription->company_id)
        ->toBe($subscription->company_id);

    expect($newSubscription->price_id)
        ->toBe($price->id);

    expect($newSubscription->previous_subscription_id)
        ->toBe($subscription->id);

    expect($newSubscription->trial_end_date)
        ->toBeNull();
});


it('renews an expired subscription', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::EXPIRED
    );

    $subscription->update([
        'end_date' => now()->subDay(),
    ]);

    $newSubscription = $service->renew(
        $subscription,
        [
            'price_id' => $price->id,
        ]
    );

    expect($newSubscription->status)
        ->toBe(SubscriptionStatus::PENDING);

    expect($newSubscription->previous_subscription_id)
        ->toBe($subscription->id);
});


it('rejects renewal of pending subscription', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    expect(fn() => $service->renew(
        $subscription,
        [
            'price_id' => $price->id,
        ]
    ))
        ->toThrow(BusinessRuleException::class);
});


it('rejects renewal of canceled subscription', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::CANCELED
    );

    expect(fn() => $service->renew(
        $subscription,
        [
            'price_id' => $price->id,
        ]
    ))
        ->toThrow(BusinessRuleException::class);
});


it('rejects renewal of trial expired subscription', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::TRAIL_EXPIRED
    );

    expect(fn() => $service->renew(
        $subscription,
        [
            'price_id' => $price->id,
        ]
    ))
        ->toThrow(BusinessRuleException::class);
});


it('rejects renewal when company already has pending subscription', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::PENDING
    );

    expect(fn() => $service->renew(
        $subscription,
        [
            'price_id' => $price->id,
        ]
    ))
        ->toThrow(BusinessRuleException::class);
});


it('rejects renewal with inactive price', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    $price->update([
        'is_active' => false,
    ]);

    expect(fn() => $service->renew(
        $subscription,
        [
            'price_id' => $price->id,
        ]
    ))
        ->toThrow(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );
});


it('rejects renewal with nonexistent price', function () {

    $service = app(SubscriptionLifecycleService::class);

    $owner = createLifecycleUser();

    $company = createLifecycleCompany($owner);

    $price = createLifecyclePrice();

    $subscription = createLifecycleSubscription(
        $company,
        $price,
        SubscriptionStatus::ACTIVE
    );

    expect(fn() => $service->renew(
        $subscription,
        [
            'price_id' => 999999,
        ]
    ))
        ->toThrow(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );
});
