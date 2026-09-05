<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as BaseActivity;

class ActivityCentral extends BaseActivity
{
    public function getConnectionName(): ?string
    {
        return tenancy()->initialized
            ? 'tenant'
            : 'mysql';
    }
}
