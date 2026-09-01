<?php

namespace Modules\Tenant\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Tenant\Events\ProjectTeams\AddProjectToTeamsEvent;
use Modules\Tenant\Events\ProjectTeams\RemoveProjectFromTeamsEvent;
use Modules\Tenant\Events\Task\CancelTaskEvent;
use Modules\Tenant\Events\Task\ProcessTaskEvent;
use Modules\Tenant\Listeners\ProjectTeams\AddProjectToTeamsListener;
use Modules\Tenant\Listeners\ProjectTeams\RemoveProjectFromTeamsListener;
use Modules\Tenant\Listeners\Task\CancelTaskListener;
use Modules\Tenant\Listeners\Task\ProcessTaskListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        AddProjectToTeamsEvent::class => [
            AddProjectToTeamsListener::class
        ],
        RemoveProjectFromTeamsEvent::class => [
            RemoveProjectFromTeamsListener::class
        ],
        ProcessTaskEvent::class => [
            ProcessTaskListener::class
        ],
        CancelTaskEvent::class => [
            CancelTaskListener::class,
        ]

    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
