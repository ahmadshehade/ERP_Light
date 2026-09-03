<?php

namespace Modules\Tenant\Support;

use App\Enums\NameOfRoles;
use Modules\Tenant\Enum\TenantPermission;

class TenantPermissionManagement
{
    public static function get(): array
    {
        return [

            NameOfRoles::Owner->value => array_map(
                fn(TenantPermission $permission) => $permission->value,
                TenantPermission::cases()
            ),

            NameOfRoles::Manager->value => [
                TenantPermission::TenantViewAnyDepartments->value,
                TenantPermission::TenantViewDepartment->value,
                TenantPermission::TenantCreateDepartment->value,
                TenantPermission::TenantUpdateDepartment->value,
                TenantPermission::TenantDeleteDepartment->value,
                TenantPermission::TenantRestoreDepartment->value,
                TenantPermission::TenantGetTrashedDepartments->value,
                TenantPermission::TenantGetAllTrashedDepartments->value,
                TenantPermission::TenantRestoreAllDepartments->value,

                //Posiotions
                TenantPermission::TenantViewAnyPositions->value,
                TenantPermission::TenantViewPosition->value,


                //Team
                TenantPermission::TenantViewAnyTeams->value,
                TenantPermission::TenantViewTeam->value,
                TenantPermission::TenantCreateTeam->value,
                TenantPermission::TenantUpdateTeam->value,
                TenantPermission::TenantDeleteTeam->value,

                //task
                TenantPermission::TenantViewAnyTask->value,
                TenantPermission::TenantViewTask->value,
                TenantPermission::TenantCreateTask->value,
                TenantPermission::TenantUpdateTask->value,
                TenantPermission::TenantDeleteTask->value,
                TenantPermission::TenantGetTrashedTasks->value,
                TenantPermission::TenantGetAllTrashedTasks->value,
                TenantPermission::TenantCompleteTask->value,
                TenantPermission::TenantOnHoldTask->value,
                TenantPermission::TenantCancelTask->value,

                //projects
                TenantPermission::TenantViewProject->value,
                TenantPermission::TenantViewAnyProjects->value,
                TenantPermission::TenantCreateProject->value,
                TenantPermission::TenantUpdateProject->value,
                TenantPermission::TenantOnHoldProject->value,
                TenantPermission::TenantResumeProject->value,

            ],

            NameOfRoles::Employee->value => [

                // Departments
                TenantPermission::TenantViewAnyDepartments->value,
                TenantPermission::TenantViewDepartment->value,

                // Teams
                TenantPermission::TenantViewAnyTeams->value,
                TenantPermission::TenantViewTeam->value,

                // Projects
                TenantPermission::TenantViewAnyProjects->value,
                TenantPermission::TenantViewProject->value,

                // Tasks
                TenantPermission::TenantViewAnyTask->value,
                TenantPermission::TenantViewTask->value,
                TenantPermission::TenantCompleteTask->value,
                TenantPermission::TenantOnHoldTask->value,
            ],

            NameOfRoles::Guest->value => [

                // Departments
                TenantPermission::TenantViewAnyDepartments->value,
                TenantPermission::TenantViewDepartment->value,

                // Teams
                TenantPermission::TenantViewAnyTeams->value,
                TenantPermission::TenantViewTeam->value,

                // Projects
                TenantPermission::TenantViewAnyProjects->value,
                TenantPermission::TenantViewProject->value,
            ],
        ];
    }
}
