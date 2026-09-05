<?php

namespace Modules\Central\Services;

use App\Enums\NameOfCache;
use App\Exceptions\BusinessRuleException;
use App\Traits\ApplyFilters;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Central\Jobs\DeleteCompanyJob;
use Modules\Central\Jobs\ProccessCompanyMediaJob;
use Modules\Central\Models\Company;
use Modules\Central\Services\Companies\CompanyNotification;
use Modules\Central\Services\Tenant\TenantProvisioningService;

class CompanyService
{
    use ApplyFilters;

    public const CACHE_TTL = 60;

    public function __construct(
        public CompanyNotification $companyNotify,

    ) {}

    /**
     * Generate cache key.
     */
    protected function genKey(
        array $data = [],
        string $prefix = ""
    ): string {
        $user = Auth::user();

        $userKey = $user
            ? $user->id
            . "_"
            . $prefix
            . implode("_", $user->roles->pluck('name')->toArray())
            : "";

        return $userKey
            . "_"
            . NameOfCache::COMPANY->value
            . "_"
            . md5(json_encode($data));
    }

    /**
     * Flush cache.
     */
    protected function flushCache(): void
    {
        Cache::tags(NameOfCache::COMPANY->value)->flush();
    }

    /**
     * Get all companies.
     */
    public function getAllCompanies(array $data = []): array
    {
        $cacheKey = $this->genKey($data, "_no_trashed_");

        return Cache::tags(NameOfCache::COMPANY->value)
            ->remember(
                $cacheKey,
                self::CACHE_TTL,
                function () use ($data) {
                    $query = Company::query()
                        ->userCompanies(Auth::user())
                        ->with([
                            'owner',
                        ]);
                    if (!empty($data)) {
                        $this->filterData($query, $data);
                    }
                    return $query->get()->toArray();
                }
            );
    }

    /**
     * Get company.
     */
    public function getCompany(Company $company): Company
    {
        return $company->load([
            'owner',
        ]);
    }

    /**
     * Create company.
     */
    public function store(array $data): Company
    {
        return DB::transaction(function () use ($data) {

            $photo = Arr::pull($data, 'photo');
            $company = new Company();
            $company->owner_id = Auth::id();
            $company->fill($data);
            $company->save();
            $path = null;
            if ($photo instanceof UploadedFile) {
                $path = $photo->store(
                    'temp/company/' . $company->id,
                    'local'
                );
            }
            DB::afterCommit(function () use ($company, $path) {
                $this->flushCache();
                if ($path !== null) {
                    ProccessCompanyMediaJob::dispatch(
                        $company->id,
                        $path
                    );
                }
                $this->companyNotify->createCompanyNotification(
                    $company
                );
            });
            return $company->load([
                'owner',
            ]);
        }, 5);
    }

    /**
     * Update company.
     */
    public function update(
        Company $company,
        array $data
    ): Company {
        return DB::transaction(function () use ($company, $data) {
            $photo = Arr::pull($data, 'photo', null);
            $company->update($data);
            $path = null;
            if ($photo instanceof UploadedFile) {
                $path = $photo->store(
                    'temp/company/' . $company->id,
                    'local'
                );
            }
            DB::afterCommit(function () use ($company, $path) {
                $this->flushCache();
                if ($path !== null) {
                    ProccessCompanyMediaJob::dispatch(
                        $company->id,
                        $path
                    );
                }
                $this->companyNotify->updateCompanyNotification(
                    $company
                );
            });
            return $company->load([
                'owner',
                'media',
            ]);
        }, 5);
    }

    /**
     * Soft delete company.
     */
    public function destroy(Company $company): bool
    {
        return DB::transaction(function () use ($company) {

            $companyId = $company->id;
            $companyNameEn = $company->getTranslation(
                'name',
                'en'
            );
            $companyNameAr = $company->getTranslation(
                'name',
                'ar'
            );
            $subDomain = $company->subdomain;
            $maxUsers = $company->max_users;
            $isActive = $company->is_active;
            $owner = $company->owner;
            $deletedAt = now();
            $company->delete();

            DB::afterCommit(function () use (
                $companyId,
                $companyNameEn,
                $companyNameAr,
                $subDomain,
                $maxUsers,
                $isActive,
                $owner,
                $deletedAt
            ) {

                $this->flushCache();

                $this->companyNotify->DeleteCompanyNotification(
                    $companyId,
                    $companyNameEn,
                    $companyNameAr,
                    $subDomain,
                    $maxUsers,
                    $isActive,
                    $owner,
                    $deletedAt
                );
            });

            return true;
        });
    }

    /**
     * Restore company.
     */
    public function restore(Company $company): Company
    {
        return DB::transaction(function () use ($company) {

            if (!$company->trashed()) {
                throw new BusinessRuleException(
                    'Company is not trashed.',
                    404
                );
            }
            $company->restore();
            DB::afterCommit(function () {
                $this->flushCache();
            });
            return $company->load([
                'owner',
            ]);
        });
    }

    /**
     * Force delete company.
     */
    public function forceDeleteTrashedCompany(
        Company $company
    ): bool {
        if (!$company->trashed()) {
            throw new BusinessRuleException(
                'Company is not trashed.',
                404
            );
        }

        DeleteCompanyJob::dispatch($company->id);

        return true;
    }

    /**
     * Get trashed company.
     */
    public function getTrashedCompany(
        Company $company
    ): Company {
        if (!$company->trashed()) {
            throw new BusinessRuleException(
                'Company is not trashed.',
                404
            );
        }
        return $company->load([
            'owner',
        ]);
    }

    /**
     * Get all trashed companies.
     */
    public function getAllTrashedCompanies(
        array $data = []
    ): array {
        $cacheKey = $this->genKey($data, "_trashed_");
        return Cache::tags(NameOfCache::COMPANY->value)
            ->remember(
                $cacheKey,
                self::CACHE_TTL,
                function () use ($data) {

                    $query = Company::onlyTrashed()
                        ->userCompanies(Auth::user())
                        ->with([
                            'owner',
                        ]);
                    if (!empty($data)) {
                        $this->filterData($query, $data);
                    }
                    return $query->get()->toArray();
                }
            );
    }

    /**
     * Force delete all trashed companies.
     */
    public function forceDeleteAllTrashedCompanies(): bool
    {
        $companies = Company::onlyTrashed()
            ->userCompanies(Auth::user())
            ->get(['id']);
        if ($companies->isEmpty()) {
            throw new BusinessRuleException(
                'No trashed companies found.',
                404
            );
        }
        foreach ($companies as $company) {
            DeleteCompanyJob::dispatch($company->id);
        }
        $this->flushCache();
        return true;
    }

    /**
     * Restore all trashed companies.
     */
    public function restoreAllTrashedCompanies(): bool
    {
        return DB::transaction(function () {
            $query = Company::onlyTrashed()
                ->userCompanies(Auth::user());
            if (!$query->exists()) {
                throw new BusinessRuleException(
                    'No trashed companies found.',
                    404
                );
            }
            $query->restore();
            DB::afterCommit(function () {
                $this->flushCache();
            });
            return true;
        });
    }
}
