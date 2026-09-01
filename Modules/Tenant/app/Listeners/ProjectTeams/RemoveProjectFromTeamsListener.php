<?php

namespace Modules\Tenant\Listeners\ProjectTeams;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Tenant\Events\ProjectTeams\RemoveProjectFromTeamsEvent;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Models\Team;
use Modules\Tenant\Notifications\ProjectTeams\RemoveProjectFromTeamsNotification;

class RemoveProjectFromTeamsListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(RemoveProjectFromTeamsEvent $event): void
    {
        $project = Project::query()
            ->with('teams.tenantUsers')
            ->find($event->projectId);

        if (!$project) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get the teams that were removed from the project
        |--------------------------------------------------------------------------
        */

        $removedTeams = Team::query()
            ->with('tenantUsers.user')
            ->whereIn('id', $event->teamIds)
            ->get();

        if ($removedTeams->isEmpty()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get all TenantUsers from removed teams
        |--------------------------------------------------------------------------
        |
        | unique('id') prevents sending the same notification twice
        | if a TenantUser belongs to more than one removed team.
        |
        */

        $tenantUsers = $removedTeams
            ->flatMap(fn($team) => $team->tenantUsers)
            ->unique('id')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Current users of the teams that are still attached to the project
        |--------------------------------------------------------------------------
        */

        $currentProjectTenantUserIds = $project->teams
            ->flatMap(fn($team) => $team->tenantUsers)
            ->pluck('id')
            ->unique();

        foreach ($tenantUsers as $tenantUser) {

            if (!$tenantUser->user) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | If the user is still a member of another project team,
            | don't send a removal notification.
            |--------------------------------------------------------------------------
            */

            if ($currentProjectTenantUserIds->contains($tenantUser->id)) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Teams that were actually removed for this user
            |--------------------------------------------------------------------------
            */

            $teams = $removedTeams
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
                new RemoveProjectFromTeamsNotification(
                    projectId: $event->projectId,
                    name_en: $event->name_en,
                    name_ar: $event->name_ar,
                    teams: $teams,
                )
            );
        }
    }
}
