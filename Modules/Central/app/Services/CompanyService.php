<?php

namespace Modules\Central\Services;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Central\Models\Company;
use Modules\Central\Services\Companies\CompanyNotification;
use RuntimeException;
use Illuminate\Support\Arr;
use Modules\Central\Jobs\ProccessCompanyMediaJob;

class CompanyService
{
    use ApplyFilters;

    public function __construct(public CompanyNotification $companyNotify) {}


    public const CACHE_TTL = 60;


    /**
     * Generate cache key
     */
    protected function genKey(array $data = [], string $prefix = ""): string
    {
        $user = Auth::user();

        $userKey = $user
            ? $user->id . "_" . $prefix . implode('_', $user->roles->pluck('name')->toArray())
            : "";
        return $userKey
            . "_" . NameOfCache::COMPANY->value
            . "_" . md5(json_encode($data));
    }
    /**
     * Flush cache
     */
    protected function flushCache(): void
    {
        Cache::tags(NameOfCache::COMPANY->value)->flush();
    }
    /**
     * Get all companies
     */
    public function getAllCompanies(array $data = []): array
    {
        $cacheKey = $this->genKey($data, "_no_trashed_");
        return Cache::tags(NameOfCache::COMPANY->value)
            ->remember($cacheKey, self::CACHE_TTL, function () use ($data) {
                $query = Company::query()
                    ->userCompanies(Auth::user())
                    ->with([
                        'owner'
                    ]);
                if (!empty($data)) {
                    $this->filterData($query, $data);
                }
                return $query->get()->toArray();
            });
    }
    /**
     * Get company
     */
    public function getCompany(Company $company): Company
    {
        return $company->load([
            'owner'
        ]);
    }
    /**
     * Create company
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
                $path = $photo->store('temp/company/' . $company->id, 'local');
            }
            DB::afterCommit(function () use ($company, $path) {
                $this->flushCache();
                if ($path !== null) {
                    ProccessCompanyMediaJob::dispatch($company->id, $path);
                }
                $this->companyNotify->createCompanyNotification($company);
            });
            return $company->load(['owner',]);
        }, 5);
    }
    /**
     * Update company
     */
    public function update(
        Company $company,
        array $data
    ): Company {
        return DB::transaction(function () use ($company, $data) {
            $photo = Arr::pull($data, 'photo', null);
            $company->update($data);
            $path = null;
            $path = null;
            if ($photo instanceof UploadedFile) {
                $path = $photo->store('temp/company/' . $company->id, 'local');
            }
            DB::afterCommit(function () use ($company, $path) {
                $this->flushCache();
                ProccessCompanyMediaJob::dispatch($company->id, $path);
                $this->companyNotify->updateCompanyNotification($company);
            });
            return $company->load([
                'owner',
                'media'
            ]);
        }, 5);
    }
    /**
     * Soft delete company
     */
    public function destroy(Company $company): bool
    {
        return DB::transaction(function () use (
            $company
        ) {
            $companyId = $company->id;
            $companyNameEn = $company->getTranslation('name', 'en');
            $companyNameAr = $company->getTranslation('name', 'ar');
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
     * Restore company
     */
    public function restore(Company $company): Company
    {
        return DB::transaction(function () use ($company) {
            if (!$company->trashed()) {

                throw new RuntimeException(
                    'Company is not trashed.'
                );
            }
            $company->restore();
            $this->flushCache();
            return $company->load([
                'owner'
            ]);
        });
    }
    /**
     * Force delete company
     */
    public function forceDeleteTrashedCompany(
        Company $company
    ): bool {
        return DB::transaction(function () use ($company) {
            if (!$company->trashed()) {

                throw new RuntimeException(
                    'Company must be trashed first.'
                );
            }
            $company->forceDelete();
            $this->flushCache();
            return true;
        });
    }
    /**
     * Get trashed company
     */
    public function getTrashedCompany(
        Company $company
    ): Company {
        if (!$company->trashed()) {

            throw new RuntimeException(
                'Company is not trashed.'
            );
        }
        return $company->load([
            'owner'
        ]);
    }
    /**
     * Get all trashed companies
     */
    public function getAllTrashedCompanies(
        array $data = []
    ): array {
        $cacheKey = $this->genKey($data, "_trashed_");
        return Cache::tags(NameOfCache::COMPANY->value)
            ->remember($cacheKey, self::CACHE_TTL, function () use ($data) {


                $query = Company::onlyTrashed()
                    ->userCompanies(Auth::user())
                    ->with([
                        'owner'
                    ]);
                if (!empty($data)) {
                    $this->filterData($query, $data);
                }
                return $query->get()->toArray();
            });
    }
    /**
     * Force delete all trashed companies
     */
    public function forceDeleteAllTrashedCompanies(): bool
    {
        $query = Company::onlyTrashed()
            ->userCompanies(Auth::user());
        if (!$query->exists()) {
            throw new RuntimeException(
                'No trashed companies found.'
            );
        }
        $query->forceDelete();
        $this->flushCache();
        return true;
    }
    /**
     * Restore all trashed companies
     */
    public function restoreAllTrashedCompanies(): bool
    {
        $query = Company::onlyTrashed()
            ->userCompanies(Auth::user());
        if (!$query->exists()) {
            throw new RuntimeException(
                'No trashed companies found.'
            );
        }
        $query->restore();
        $this->flushCache();
        return true;
    }
}
