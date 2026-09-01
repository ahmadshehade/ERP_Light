<?php

namespace App\Enums;


enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case CANCELED = 'canceled';
    case EXPIRED = 'expired';
    case TRAIL = 'trial';
    case TRAIL_EXPIRED = 'trial_expired';
}
