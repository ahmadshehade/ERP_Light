<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Central\Models\SubscriptionPlan;


class SubscriptionPlanFactory extends Factory
{

    protected $model = SubscriptionPlan::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'en' => fake()->name(),
                'ar' => fake()->name(),
            ],
            'description' => [
                'en' => fake()->name(),
                'ar' => fake()->name(),
            ],
            'is_active' => fake()->boolean(),
        ];
    }
}
