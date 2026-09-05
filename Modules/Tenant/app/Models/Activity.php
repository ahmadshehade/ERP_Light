<?php

namespace Modules\Tenant\Models;

use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    protected $connection = 'tenant';
}
