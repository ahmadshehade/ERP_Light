<?php

namespace Database\Factories;

use App\Enums\PriceInterval;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;

/**
 * @extends Factory<SubscriptionPrice>
 */
class SubscrtiptionPriceFactory extends Factory
{

    protected  $model = SubscriptionPrice::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => SubscriptionPlan::query()->inRandomOrder()
                ->where('is_active', true)->value('id'),
            'price' => $this->faker->randomFloat(2, 1, 100),
            'interval' => $this->faker->randomElement(PriceInterval::cases()),
            'stripe_price_id' => $this->faker->uuid,
            'is_active' => $this->faker->boolean(),
            'has_trial' => $this->faker->boolean(),
            'trial_days' => $this->faker->numberBetween(1, 30),
        ];
    }
}
