<?php

namespace Modules\Tenant\Enum;


enum  TaskStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case OnHold = 'on_hold';
}
