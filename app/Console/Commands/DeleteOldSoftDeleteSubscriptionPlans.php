<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Central\Models\SubscriptionPlan;

#[Signature('app:delete-old-soft-delete-subscription-plans')]
#[Description('Command description')]
class DeleteOldSoftDeleteSubscriptionPlans extends Command
{
    protected $signature = 'subscription-plans:cleanup';

    protected $description = 'Delete Old Subscription Plans Older Than 180 Days';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = SubscriptionPlan::query()->onlyTrashed()->where('deleted_at', '<=', Carbon::now()->subDays(180))->forceDelete();
        $this->info("Deleted {$count} subscription plans.");
        return self::SUCCESS;
    }
}
