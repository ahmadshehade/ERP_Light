<?php

namespace Modules\Tenant\Listeners\ProjectTeams;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Tenant\Events\ProjectTeams\AddProjectToTeamsEvent;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Notifications\ProjectTeams\AddProjectToTeamsNotification;

class AddProjectToTeamsListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(AddProjectToTeamsEvent $event): void
    {
        $project = Project::query()
            ->with('teams.tenantUsers.user')
            ->find($event->projectId);

        if (!$project) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get all users from all project teams
        |--------------------------------------------------------------------------
        |
        | unique('id') prevents sending the same notification twice
        | if a TenantUser belongs to more than one team.
        |
        */

        $tenantUsers = $project->teams
            ->flatMap(fn($team) => $team->tenantUsers)
            ->unique('id')
            ->values();

        foreach ($tenantUsers as $tenantUser) {

            if (!$tenantUser->user) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Teams this user belongs to in this project
            |--------------------------------------------------------------------------
            */

            $teamNames = $project->teams
                ->filter(
                    fn($team) =>
                    $team->tenantUsers->contains('id', $tenantUser->id)
                )
                ->map(
                    fn($team) => [
                        'id' => $team->id,
                        'name_en' => $team->name_en,
                        'name_ar' => $team->name_ar,
                    ]
                )
                ->values()
                ->toArray();

            $tenantUser->user->notify(
                new AddProjectToTeamsNotification(
                    projectId: $event->projectId,
                    name_en: $event->name_en,
                    name_ar: $event->name_ar,
                    teams: $teamNames,
                )
            );
        }
    }
}
