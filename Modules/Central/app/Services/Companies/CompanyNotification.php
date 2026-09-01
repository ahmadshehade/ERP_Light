<?php

namespace Modules\Central\Services\Companies;

use App\Enums\NameOfRoles;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Modules\Central\Models\Company;
use Modules\Central\Notifications\Api\V1\Companies\CreateCompanyNotification;
use Modules\Central\Notifications\Api\V1\Companies\DeleteCompanyNotification;
use Modules\Central\Notifications\Api\V1\Companies\UpdateCompanyNotification;

class CompanyNotification
{

    /**
     * Initail Company
     * @param Company $company
     * @return array
     */
    private function initailCompany(Company $company): array
    {
        $owner = $company->owner;
        $admins = User::role(NameOfRoles::SuperAdmin->value)->get();
        $senders = $admins->push($owner);
        return [
            'companyNameEn' => $company->getTranslation('name', 'en'),
            'companyNameAr' => $company->getTranslation('name', 'ar'),
            'senders' => $senders,
            'isActive' => (bool)$company->is_active,
            'maxUsers' => $company->max_users,
            'subDomain' => $company->subdomain
        ];
    }

    /**
     * Send the company created notification.
     * @param Company $company
     * @return void
     */
    public function createCompanyNotification(Company $company): void
    {
        Notification::send($this->initailCompany($company)['senders'], new CreateCompanyNotification(
            $company->id,
            $this->initailCompany($company)['companyNameEn'],
            $this->initailCompany($company)['companyNameAr'],
            $this->initailCompany($company)['subDomain'],
            $this->initailCompany($company)['maxUsers'],
            $this->initailCompany($company)['isActive']
        ));
    }


    /**
     * Send the company deleted notification.
     * @param int $companyId
     * @param string $companyNameEn
     */
    public function DeleteCompanyNotification(
        int $companyId,
        string $companyNameEn,
        string $companyNameAr,
        string $subDomain,
        int $maxUsers,
        bool $isActive,
        User $owner,
        Carbon $deletedAt


    ): void {

        $admins = User::role(NameOfRoles::SuperAdmin->value)->get();
        $senders = $admins->push($owner);
        Notification::send($senders, new DeleteCompanyNotification(
            $companyId,
            $companyNameEn,
            $companyNameAr,
            $subDomain,
            $maxUsers,
            $isActive,
            $owner,
            $deletedAt
        ));
    }

    /**
     * Send the company updated notification.
     * @param Company $company
     * @return void
     */
    public function UpdateCompanyNotification(Company $company): void
    {
        Notification::send($this->initailCompany($company)['senders'], new UpdateCompanyNotification(
            $company->id,
            $this->initailCompany($company)['companyNameEn'],
            $this->initailCompany($company)['companyNameAr'],
            $this->initailCompany($company)['subDomain'],
            $this->initailCompany($company)['maxUsers'],
            $this->initailCompany($company)['isActive']
        ));
    }
}
