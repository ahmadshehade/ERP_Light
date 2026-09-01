<?php

namespace Modules\Central\Services\SubscriptionPrices;

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Notifications\Api\V1\SupscriptionPrices\CreateSubscriptionPriceNotify;
use Modules\Central\Notifications\Api\V1\SupscriptionPrices\DeActiveSubscriptionPriceNotify;
use Modules\Central\Notifications\Api\V1\SupscriptionPrices\UpdateSubscriptionPriceNotify;

class   SubscriptionPriceNotification
{


    /**
     *  Summary of activeNotification
     * @prama SubscriptionPrice $price
     * @return void
     */
    public function activeNotification(SubscriptionPrice $price): void
    {
        Notification::send(
            $this->initailPriceNotification($price)['owners'],
            new CreateSubscriptionPriceNotify(
                $this->initailPriceNotification($price)['priceId'],
                $this->initailPriceNotification($price)['planName_en'],
                $this->initailPriceNotification($price)['planName_ar'],
                $this->initailPriceNotification($price)['price'],
                $this->initailPriceNotification($price)['interval'],
                $this->initailPriceNotification($price)['has_trial'],
                $this->initailPriceNotification($price)['trial_days'],
                $this->initailPriceNotification($price)['is_active']
            )
        );
    }

    /**
     * Summary of updatePriceNotify
     * @prama SubscriptionPrice $price
     * @return void
     */
    public function updatePriceNotify(SubscriptionPrice $price): void
    {
        Notification::send(
            $this->initailPriceNotification($price)['owners'],
            new UpdateSubscriptionPriceNotify(
                $this->initailPriceNotification($price)['priceId'],
                $this->initailPriceNotification($price)['planName_en'],
                $this->initailPriceNotification($price)['planName_ar'],
                $this->initailPriceNotification($price)['price'],
                $this->initailPriceNotification($price)['interval'],
                $this->initailPriceNotification($price)['has_trial'],
                $this->initailPriceNotification($price)['trial_days'],
                $this->initailPriceNotification($price)['is_active']
            )
        );
    }

    /**
     * Summary of deActiveNotify
     * @prama SubscriptionPrice $price
     * @return void
     */
    public function deActiveNotify(SubscriptionPrice $price): void
    {
        Notification::send(
            $this->initailPriceNotification($price)['owners'],
            new DeActiveSubscriptionPriceNotify(
                $this->initailPriceNotification($price)['priceId'],
                $this->initailPriceNotification($price)['planName_en'],
                $this->initailPriceNotification($price)['planName_ar'],
                $this->initailPriceNotification($price)['is_active']
            )
        );
    }

    /**
     * Summary of initailPriceNotification
     * @prama SubscriptionPrice $price
     * @return array
     */
    protected function initailPriceNotification(SubscriptionPrice $price): array
    {
        $owners = User::role(NameOfRoles::Owner->value)->get();
        return [
            'owners' => $owners,
            'priceId' => $price->id,
            'planName_en' => $price->plan->getTranslation('name', 'en'),
            'planName_ar' => $price->plan->getTranslation('name', 'ar'),
            'price' => $price->price,
            'interval' => $price->interval->value,
            'has_trial' => $price->has_trial,
            'trial_days' => $price->trial_days,
            'is_active' => $price->is_active
        ];
    }
}
