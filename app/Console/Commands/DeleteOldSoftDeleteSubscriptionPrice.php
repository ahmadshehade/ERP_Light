<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Central\Models\SubscriptionPrice;


class DeleteOldSoftDeleteSubscriptionPrice extends Command
{
    protected $signature = 'subscription-price:cleanup';

    protected $description = 'Delete Old Subscription Price Older Than 180 Days';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = SubscriptionPrice::query()->onlyTrashed()->where('deleted_at', '<=', now()->subDays(180))->forceDelete();
        $this->info("Deleted {$count} old soft deleted subscription prices.");
        return self::SUCCESS;
    }
}
