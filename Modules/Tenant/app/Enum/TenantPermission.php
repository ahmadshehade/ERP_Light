<?php

namespace Modules\Tenant\Enum;

enum TenantPermission: string
{
    //Tenant User
    case TenantViewAnyUsers = 'tenant_view_any_users';
    case TenantViewUser = 'tenant_view_user';
    case TenantCreateUser = 'tenant_create_user';
    case TenantUpdateUser = 'tenant_update_user';
    case TenantDeleteUser = 'tenant_delete_user';

        //Roles
    case TenantViewAnyRoles = 'tenant_view_any_roles';
    case TenantViewRole = 'tenant_view_role';
    case TenantAssignRoleToUser = 'tenant_assign_role_to_user';
    case TenantRemoveRoleFromUser = 'tenant_remove_role_from_user';
    case TenantSyncRolesToUser = 'tenant_sync_roles_to_user';


        //permissions
    case TenantViewAnyPermissions = 'tenant_view_any_permissions';
    case TenantViewPermission = 'tenant_view_permission';
    case TenantSyncPermissionsToRole = 'tenant_sync_permissions_to_role';
    case TenantGivePermissionToUser = 'tenant_give_permission_to_user';
    case TenantRevokePermissionFromUser = 'tenant_revoke_permission_from_user';


        //department
    case TenantViewAnyDepartments = 'tenant_view_any_departments';
    case TenantViewDepartment = 'tenant_view_department';
    case TenantCreateDepartment = 'tenant_create_department';
    case TenantUpdateDepartment = 'tenant_update_department';
    case TenantDeleteDepartment = 'tenant_delete_department';
    case TenantRestoreDepartment = 'tenant_restore_department';
    case TenantForceDeleteDepartment = 'tenant_force_delete_department';
    case TenantRestoreAllDepartments = 'tenant_restore_all_departments';
    case TenantForceDeleteAllDepartments = 'tenant_force_delete_all_departments';
    case TenantGetTrashedDepartments = 'tenant_get_trashed_departments';
    case TenantGetAllTrashedDepartments = 'tenant_get_all_trashed_departments';


        //positions
    case TenantCreatePosition = 'tenant_create_position';
    case TenantViewAnyPositions = 'tenant_view_any_positions';
    case TenantViewPosition = 'tenant_view_position';
    case TenantUpdatePosition = 'tenant_update_position';
    case TenantDeletePosition = 'tenant_delete_position';
    case TenantRestorePosition = 'tenant_restore_position';
    case TenantForceDeletePosition = 'tenant_force_delete_position';
    case TenantRestoreAllPositions = 'tenant_restore_all_positions';
    case TenantForceDeleteAllPositions = 'tenant_force_delete_all_positions';
    case TenantGetTrashedPositions = 'tenant_get_trashed_positions';
    case TenantGetAllTrashedPositions = 'tenant_get_all_trashed_positions';

        //Teams
    case TenantViewAnyTeams = 'tenant_view_any_teams';
    case TenantViewTeam = 'tenant_view_team';
    case TenantCreateTeam = 'tenant_create_team';
    case TenantUpdateTeam = 'tenant_update_team';
    case TenantDeleteTeam = 'tenant_delete_team';


        //Projects
    case TenantViewAnyProjects = 'tenant_view_any_projects';
    case TenantViewProject = 'tenant_view_project';
    case TenantCreateProject = 'tenant_create_project';
    case TenantUpdateProject = 'tenant_update_project';
    case TenantDeleteProject = 'tenant_delete_project';
    case TenantRestoreProject = 'tenant_restore_project';
    case TenantForceDeleteProject = 'tenant_force_delete_project';
    case TenantRestoreAllProjects = 'tenant_restore_all_projects';
    case TenantForceDeleteAllProjects = 'tenant_force_delete_all_projects';
    case TenantGetTrashedProjects = 'tenant_get_trashed_projects';
    case TenantGetAllTrashedProjects = 'tenant_get_all_trashed_projects';

        //actionProjects
    case TenantOnHoldProject = 'tenant_on_hold_project';
    case TenantCompleteProject = 'tenant_complete_project';
    case TenantCancelProject = 'tenant_cancel_project';
    case TenantResumeProject = 'tenant_resume_project';

        //tasks
    case TenantViewAnyTask = 'tenant_view_any_tasks';
    case TenantViewTask = 'tenant_view_task';
    case TenantCreateTask = 'tenant_create_task';
    case TenantUpdateTask = 'tenant_update_task';
    case TenantDeleteTask = 'tenant_delete_task';
    case TenantRestoreTask = 'tenant_restore_task';
    case TenantForceDeleteTask = 'tenant_force_delete_task';
    case TenantRestoreAllTasks = 'tenant_restore_all_tasks';
    case TenantForceDeleteAllTasks = 'tenant_force_delete_all_tasks';
    case TenantGetTrashedTasks = 'tenant_get_trashed_tasks';
    case TenantGetAllTrashedTasks = 'tenant_get_all_trashed_tasks';
    case TenantCompleteTask = 'tenant_complete_task';
    case TenantCancelTask = 'tenant_cancel_task';
    case TenantOnHoldTask = 'tenant_on_hold_task';
}
