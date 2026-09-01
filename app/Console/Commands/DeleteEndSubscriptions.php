<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use Illuminate\Console\Command;
use Modules\Central\Models\Subscription;

class DeleteEndSubscriptions extends Command
{

    protected $signature = 'subscriptions:delete-end';

    protected $description = 'Soft delete expired subscriptions older than 30 days';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Subscription::query()->where('status', SubscriptionStatus::EXPIRED->value)
            ->where('end_date', '<=', now()->subDays(30))->delete();

        $this->info("Deleted {$count} end subscriptions.");
        return self::SUCCESS;
    }
}
