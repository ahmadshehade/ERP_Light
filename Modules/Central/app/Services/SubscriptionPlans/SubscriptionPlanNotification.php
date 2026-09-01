<?php

namespace Modules\Central\Services\SubscriptionPlans;

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Notifications\Api\V1\SubscriptionPlans\CreatePlanNotification;
use Modules\Central\Notifications\Api\V1\SubscriptionPlans\DeActiveSubscriptionPlanNotification;
use Modules\Central\Notifications\Api\V1\SubscriptionPlans\UpdatePlanNotification;

class SubscriptionPlanNotification
{

    public function activeNotification(SubscriptionPlan $plan): void
    {


        Notification::send(
            $this->inisialPlanNotification($plan)['owners'],
            new CreatePlanNotification(
                $this->inisialPlanNotification($plan)['name_ar'],
                $this->inisialPlanNotification($plan)['description_ar'],
                $this->inisialPlanNotification($plan)['name_en'],
                $this->inisialPlanNotification($plan)['description_en']

            )
        );
    }


    public function updateNotification(SubscriptionPlan $plan): void
    {
        Notification::send(
            $this->inisialPlanNotification($plan)['owners'],
            new UpdatePlanNotification(
                $this->inisialPlanNotification($plan)['name_ar'],
                $this->inisialPlanNotification($plan)['description_ar'],
                $this->inisialPlanNotification($plan)['name_en'],
                $this->inisialPlanNotification($plan)['description_en']
            )
        );
    }

    public function deActivePlanNotification(SubscriptionPlan $plan): void
    {
        Notification::send(
            $this->inisialPlanNotification($plan)['owners'],
            new DeActiveSubscriptionPlanNotification(
                $this->inisialPlanNotification($plan)['name_en'],
                $this->inisialPlanNotification($plan)['name_en'],
                $this->inisialPlanNotification($plan)['is_active'],
            )
        );
    }



    protected function inisialPlanNotification(SubscriptionPlan $plan): array
    {
        $owners = User::role(NameOfRoles::Owner->value)->get();
        return [
            'owners' => $owners,
            'name_ar' => $plan->getTranslation('name', 'ar'),
            'name_en' => $plan->getTranslation('name', 'en'),
            'description_ar' => $plan->getTranslation('description', 'ar'),
            'description_en' => $plan->getTranslation('description', 'en'),
            'is_active' => $plan->is_active
        ];
    }
}
