<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;


class DeleteOldSoftDeleteUsers extends Command
{

    protected  $signature = 'users:cleanup';

    protected  $description = 'Delete users older than 180 days';
    /**
     * Execute the console command.
     */
    public function handle()
    {

        $count = User::query()->onlyTrashed()->where('deleted_at', '<=', Carbon::now()->subDays(180))->forceDelete();
        $this->info("Deleted {$count} users.");

        return self::SUCCESS;
    }
}
