<?php

namespace Modules\Tenant\Services\Team;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Team;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Notifications\Team\DeleteTeamNotification;
use Modules\Tenant\Notifications\Team\StoreTeamNotification;
use Modules\Tenant\Notifications\Team\UpdateTeamNotification;

class TeamNotificationService
{

    /**
     * Summary of prepareData
     * @param Team $team
     * @return array
     */
    protected function prepareData(Team $team): array
    {
        $tenant = tenant();
        $company = $tenant->company;

        $data = [
            'name_en' => $team->getTranslation('name', 'en'),
            'name_ar' => $team->getTranslation('name', 'ar'),
            'description_en' => $team->getTranslation('description', 'en'),
            'description_ar' => $team->getTranslation('description', 'ar'),
            'is_active' => $team->is_active,
            'company_name_en' => $company->getTranslation('name', 'en'),
            'company_name_ar' => $company->getTranslation('name', 'ar'),
        ];
        return $data;
    }


    /**
     * Summary of prepareReplay
     * @return Collection
     */
    protected function prepareRecipients(): Collection
    {
        $owner = TenantUser::role(
            TenantRoles::Owner->value
        )->with('user')->first()?->user;
        $managers = TenantUser::role(
            TenantRoles::Manager->value
        )->with('user')->get()->pluck('user');

        logger()->info('managers:' . $managers);

        return collect([$owner])
            ->merge($managers)
            ->filter()
            ->unique('id')->values();
    }

    /**
     * Summary of createNewTeamNotify
     * @param Team $team
     * @return void
     */
    public function createNewTeamNotify(Team $team): void
    {
        $receivers = $this->prepareRecipients();
        $preparedData = $this->prepareData($team);
        Notification::send(
            $receivers,
            new StoreTeamNotification(
                $preparedData['name_en'],
                $preparedData['name_ar'],
                $preparedData['description_en'],
                $preparedData['description_ar'],
                $preparedData['is_active'],
                $preparedData['company_name_en'],
                $preparedData['company_name_ar']
            )
        );
    }


    /**
     * Summary of updateTeamNotify
     * @param Team $team
     * @return void
     */
    public  function updateTeamNotify(Team $team): void
    {
        $receivers = $this->prepareRecipients();
        $preparedData = $this->prepareData($team);
        Notification::send(
            $receivers,
            new UpdateTeamNotification(
                $preparedData['name_en'],
                $preparedData['name_ar'],
                $preparedData['description_en'],
                $preparedData['description_ar'],
                $preparedData['is_active'],
                $preparedData['company_name_en'],
                $preparedData['company_name_ar']
            )
        );
    }


    public function removeTeamNotify(array $data): void
    {
        $rplayers = $this->prepareRecipients();
        $tenant = tenant();
        $company = $tenant->company;
        Notification::send(
            $rplayers,
            new DeleteTeamNotification(
                $data['name_en'],
                $data['name_ar'],
                $data['description_en'],
                $data['description_ar'],
                $data['is_active'],
                $company->getTranslation('name', 'en'),
                $company->getTranslation('name', 'ar')
            )
        );
    }
}
