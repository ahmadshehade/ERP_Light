<?php

namespace Modules\Tenant\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Tenant\Console\ProccessScheduledProjects;
use Modules\Tenant\Console\ProccessScheduledTasks;

class TenantServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Tenant';



    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'tenant';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        ProccessScheduledTasks::class,
        ProccessScheduledProjects::class
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param $schedule
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        if (app()->environment('testing')) {
            return;
        }
        $schedule->command('tenant:process-scheduled-tasks')->everySecond();
        $schedule->command('tenant:process-scheduled-projects')->everyTwoHours();
    }
}
