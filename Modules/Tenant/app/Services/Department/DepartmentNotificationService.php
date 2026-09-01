<?php

namespace Modules\Tenant\Services\Department;

use Illuminate\Support\Facades\Notification;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Department;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Notifications\Departments\CreateDepartmentNotification;
use Modules\Tenant\Notifications\Departments\DeleteDepartmentNotification;
use Modules\Tenant\Notifications\Departments\DeleteDepartmentNotitification;
use Modules\Tenant\Notifications\Departments\RestoreDepartmentNotification;
use Modules\Tenant\Notifications\Departments\UpdateDepartmentNotification;

class DepartmentNotificationService
{

    /**
     * Summary of createDepartmentNotify
     * @param  \Modules\Tenant\Models\Department  $department
     * @return void
     */
    public function createDepartmentNotify(Department $department): void
    {
        $tenantUsers = TenantUser::role([
            TenantRoles::Owner->value,
            TenantRoles::Manager->value,
            TenantRoles::Employee->value,
        ])->get();
        $receivers = $tenantUsers
            ->map(fn(TenantUser $tenantUser) => $tenantUser->user)
            ->filter()
            ->values()
            ->all();
        Notification::send(
            $receivers,
            new CreateDepartmentNotification(
                $department->id,
                $department->getTranslation('name', 'en'),
                $department->getTranslation('name', 'ar'),
                $department->getTranslation('description', 'ar'),
                $department->getTranslation('description', 'en'),
                $department->is_active
            )
        );
    }

    /**
     * Summary of updatedDepartmentNotify
     * @param  \Modules\Tenant\Models\Department  $department
     * @return void
     */

    public function updatedDepartmentNotify(Department $department): void
    {
        $roles = $department->is_active
            ? [
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
                TenantRoles::Employee->value,
            ]
            : [
                TenantRoles::Owner->value,
                TenantRoles::Manager->value,
            ];
        $receivers = TenantUser::role($roles)
            ->get()
            ->map(fn(TenantUser $tenantUser) => $tenantUser->user)
            ->filter()
            ->values()
            ->all();
        if (empty($receivers)) {
            return;
        }
        Notification::send(
            $receivers,
            new UpdateDepartmentNotification(
                $department->id,
                $department->getTranslation('name', 'en'),
                $department->getTranslation('name', 'ar'),
                $department->getTranslation('description', 'ar'),
                $department->getTranslation('description', 'en'),
                $department->is_active
            )
        );
    }

    /**
     * Summary of deleteDepartmentNotify
     * @param  array  $data
     */
    public function deleteDepartmentNotify(
        array $data
    ): void {
        $tenantUsers = TenantUser::role([
            TenantRoles::Owner->value,
            TenantRoles::Manager->value,
        ])->get();
        $receivers = $tenantUsers
            ->map(fn(TenantUser $tenantUser) => $tenantUser->user)
            ->filter()
            ->values()
            ->all();
        Notification::send(
            $receivers,
            new DeleteDepartmentNotification(
                $data['department_id'],
                $data['name_en'],
                $data['name_ar'],
                $data['description_en'],
                $data['description_ar'],
                $data['is_active']
            )
        );
    }


    /**
     * Summary of retoreNotify
     * @param  array  $data
     * @return void
     */
    public function restoreNotify(array $data)
    {
        $tenantUsers = TenantUser::role([
            TenantRoles::Owner->value,
            TenantRoles::Manager->value,
        ])->get();
        $receivers = $tenantUsers
            ->map(fn(TenantUser $tenantUser) => $tenantUser->user)
            ->filter()
            ->values()
            ->all();
        Notification::send(
            $receivers,
            new RestoreDepartmentNotification(
                $data['department_id'],
                $data['name_en'],
                $data['name_ar'],
                $data['description_en'],
                $data['description_ar'],
                $data['is_active']
            )
        );
    }
}
