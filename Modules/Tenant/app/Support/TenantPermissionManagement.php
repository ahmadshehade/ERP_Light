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
                TenantPermission::TenantCreateDepartment->value,
                TenantPermission::TenantUpdateDepartment->value,
                TenantPermission::TenantDeleteDepartment->value,

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

            ],

            NameOfRoles::Employee->value => [
                TenantPermission::TenantViewAnyDepartments->value,
                TenantPermission::TenantViewDepartment->value,
                TenantPermission::TenantViewTeam->value,
            ],

            NameOfRoles::Guest->value => [
                TenantPermission::TenantViewAnyDepartments->value,
                TenantPermission::TenantViewDepartment->value,
                TenantPermission::TenantViewAnyTeams->value,
            ],
        ];
    }
}
