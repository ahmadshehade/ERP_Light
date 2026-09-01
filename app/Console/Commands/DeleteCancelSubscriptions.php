<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use Illuminate\Console\Command;
use Modules\Central\Models\Subscription;

class DeleteCancelSubscriptions extends Command
{
    protected $signature = 'subscriptions:delete-canceled';

    protected $description = 'Delete canceled subscriptions';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Subscription::query()
            ->where('status', SubscriptionStatus::CANCELED->value)
            ->where('canceled_at', '<=', now()->subDays(30))
            ->delete();

        $this->info("{$count} canceled subscriptions deleted.");
        return self::SUCCESS;
    }
}
