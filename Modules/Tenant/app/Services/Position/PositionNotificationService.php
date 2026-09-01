<?php

namespace Modules\Tenant\Services\Position;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Position;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Notifications\Position\CreateNewPositionNotification;
use Modules\Tenant\Notifications\Position\DeletePositionNotification;
use Modules\Tenant\Notifications\Position\RestorePositionNotification;
use Modules\Tenant\Notifications\Position\UpdatePositionNotification;

class PositionNotificationService
{
    /**
     * Send notification when a new position is created.
     */
    public function createNewPositionNotify(Position $position): void
    {
        $details = $this->prepareData($position);
        $receivers = $this->getReceivers();
        if ($receivers->isEmpty()) {
            return;
        }

        Notification::send(
            $receivers,
            new CreateNewPositionNotification(
                $details['position_id'],
                $details['position_name_en'],
                $details['position_name_ar'],
                $details['position_description_en'],
                $details['position_description_ar'],
                $details['is_active'],
                $details['company_name_en'],
                $details['company_name_ar'],
                $details['uuid']
            )
        );
    }

    /**
     * Send notification when a position is updated.
     */
    public function updatePositionNotify(Position $position): void
    {
        $details = $this->prepareData($position);
        $receivers = $this->getReceivers();
        if ($receivers->isEmpty()) {
            return;
        }
        Notification::send(
            $receivers,
            new UpdatePositionNotification(
                $details['position_id'],
                $details['position_name_en'],
                $details['position_name_ar'],
                $details['position_description_en'],
                $details['position_description_ar'],
                $details['is_active'],
                $details['company_name_en'],
                $details['company_name_ar'],
                $details['uuid']
            )
        );
    }


    /**
     * Send notification when a position is deleted.
     */
    public function deletePositionNotify(array $data): void
    {
        $replayers = $this->getReceivers();
        Notification::send(
            $replayers,
            new DeletePositionNotification(
                $data['position_id'],
                $data['position_name_en'],
                $data['position_name_ar'],
                $data['position_description_en'],
                $data['position_description_ar'],
                $data['is_active'],
                $data['company_name_en'],
                $data['company_name_ar'],
                $data['uuid']
            )
        );
    }


    /**
     * Send notification when a position is restored.
     */
    public function restoreNotify(Position  $position)
    {
        $replayers = $this->getReceivers();
        $details = $this->prepareData($position);
        Notification::send(
            $replayers,
            new RestorePositionNotification(
                $details['position_id'],
                $details['position_name_en'],
                $details['position_name_ar'],
                $details['position_description_en'],
                $details['position_description_ar'],
                $details['is_active'],
                $details['company_name_en'],
                $details['company_name_ar'],
                $details['uuid'],
            )
        );
    }

    /**
     * Get notification receivers.
     */
    protected function getReceivers(): Collection
    {
        $owner = TenantUser::role(
            TenantRoles::Owner->value
        )
            ->with('user')
            ->first()?->user;
        $managers = TenantUser::role(
            TenantRoles::Manager->value
        )
            ->with('user')
            ->get()
            ->pluck('user');
        return collect([$owner])
            ->merge($managers)
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Prepare position notification data.
     */
    protected function prepareData(Position $position): array
    {
        $company = tenant()->company;
        return [
            'position_id' => $position->id,
            'position_name_en' => $position->getTranslation(
                'name',
                'en'
            ),
            'position_name_ar' => $position->getTranslation(
                'name',
                'ar'
            ),
            'position_description_en' => $position->getTranslation(
                'description',
                'en'
            ),
            'position_description_ar' => $position->getTranslation(
                'description',
                'ar'
            ),
            'is_active' => (bool) $position->is_active,
            'company_name_en' => $company->getTranslation(
                'name',
                'en'
            ),
            'company_name_ar' => $company->getTranslation(
                'name',
                'ar'
            ),
            'uuid' => $position->uuid
        ];
    }
}
