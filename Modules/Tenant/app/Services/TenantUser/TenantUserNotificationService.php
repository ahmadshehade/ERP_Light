<?php

namespace Modules\Tenant\Services\TenantUser;

use Illuminate\Support\Facades\Notification;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Notifications\TenantUser\AddUserToTenantNotification;
use Modules\Tenant\Notifications\TenantUser\RemoveUserFormTenantNotification;
use Modules\Tenant\Notifications\TenantUser\RemoveUserFromTenantNotification;
use Modules\Tenant\Notifications\TenantUser\UpdateUserInTenantNotification;
use Stripe\Collection;

class TenantUserNotificationService
{
    /**
     * Send notification when a user is added to the tenant.
     * @return void
     */
    public function addUserToTenantNotify(TenantUser $tenantUser): void
    {
        $company = $this->getTenant();
        $relations = $this->getRelations($tenantUser);
        $receivers = $this->getReceivers($tenantUser);
        Notification::send(
            $receivers,
            new AddUserToTenantNotification(
                $tenantUser->id,
                $tenantUser->user->name,
                $tenantUser->user->email,
                $tenantUser->is_active,
                $company['companyNameEn'],
                $company['companyNameAr'],
                $relations['departments'],
                $relations['positions'],
                $relations['teams']
            )
        );
    }


    /**
     * Send notification when a user is updated
     * @param TenantUser $tenantUser
     * @return void
     */
    public function  updateUserTenantNotify(TenantUser $tenantUser): void
    {
        $company = $this->getTenant();
        $relations = $this->getRelations($tenantUser);
        $receivers = $this->getReceivers($tenantUser);
        Notification::send($receivers, new UpdateUserInTenantNotification(
            $tenantUser->id,
            $tenantUser->user->name,
            $tenantUser->user->email,
            $tenantUser->is_active,
            $company['companyNameEn'],
            $company['companyNameAr'],
            $relations['departments'],
            $relations['positions'],
            $relations['teams']

        ));
    }

    /**
     * Send Notification when a user is removed from the tenant
     * @param array $data
     * @return void
     */
    public  function removeUserFromTenantNotify(array $data): void
    {
        $company = $this->getTenant();
        $owner = TenantUser::role(
            TenantRoles::Owner->value
        )->with('user')->first()?->user;


        $receivers = collect([
            $owner,
            $data['user'],
        ])->filter()
            ->unique('id')
            ->values();
        Notification::send(
            $receivers,
            new RemoveUserFromTenantNotification(
                $data['tenant_user_id'],
                $data['name'],
                $data['email'],
                $company['companyNameEn'],
                $company['companyNameAr'],
            )
        );
    }


    /**
     * Summary of getRelations
     * @param TenantUser $tenantUser
     * @return array
     */
    protected function  getRelations(TenantUser $tenantUser): array
    {
        return [
            'departments' => $tenantUser->departments->map(fn($department) => [
                'id' => $department->id,
                'name_en' => $department->getTranslation('name', 'en'),
                'name_ar' => $department->getTranslation('name', 'ar'),

            ])->values()->all(),
            'positions' => $tenantUser->positions->map(fn($position) => [
                'id' => $position->id,
                'name_en' => $position->getTranslation('name', 'en'),
                'name_ar' => $position->getTranslation('name', 'ar'),
            ])->values()->all(),
            'teams' => $tenantUser->teams->map(fn($team) => [
                'id' => $team->id,
                'name_en' => $team->getTranslation('name', 'en'),
                'name_ar' => $team->getTranslation('name', 'ar')
            ])->values()->all(),
        ];
    }

    /**
     * Summary of getReceivers
     * @param TenantUser $tenantUser
     * @return mixed
     */
    public function getReceivers(TenantUser $tenantUser): mixed
    {
        $owner = TenantUser::role(
            TenantRoles::Owner->value
        )->with('user')->first()?->user;
        $user = $tenantUser->user;

        $receivers = collect([
            $owner,
            $user,
        ])->filter()
            ->unique('id')
            ->values();

        return $receivers;
    }


    /**
     * Summary Of getTenant
     * @return array
     */
    protected function getTenant(): array
    {
        $tenant = tenant();
        $company = $tenant->company;
        $companyNameEn = $company->getTranslation('name', 'en');
        $companyNameAr = $company->getTranslation('name', 'ar');
        return [
            'companyNameEn' => $companyNameEn,
            'companyNameAr' => $companyNameAr,
        ];
    }
}
