<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Central\Models\Subscription;

class DeleteOldSoftSubscription extends Command
{

    protected $signature = 'subscriptions:cleanup';

    protected $description = 'Delete old soft deleted subscriptions Older than 180 days';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Subscription::query()->onlyTrashed()->where('deleted_at', '<=', now()->subDays(180))->forceDelete();
        $this->info("Deleted {$count} old soft deleted subscriptions.");
        return self::SUCCESS;
    }
}
