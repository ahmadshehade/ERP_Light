<?php

namespace Modules\Central\Http\Controllers\Api\V1\Central;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Central\Services\CompanyService;
use Modules\Central\Http\Requests\Api\V1\Central\Company\StoreCompanyRequest;
use Modules\Central\Http\Requests\Api\V1\Central\Company\UpdateCompanyRequest;
use Modules\Central\Models\Company;


class CompanyController extends Controller
{
    use AuthorizesRequests;

    public function __construct(public CompanyService $companyService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Company::class);
        $filters = $request->only(['owner_id', 'name', 'subdomain', 'is_active']);
        $companies = $this->companyService->getAllCompanies($filters);
        return $this->successMessage('Successfully retrieved companies', ['companies' => $companies], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        $this->authorize('create', Company::class);

        $validatedData = $request->validated();
        $company = $this->companyService->store($validatedData);
        return $this->successMessage('Successfully created company', ['company' => $company], 201);
    }
    /**
     * Show the specified resource.
     */
    public function show(Company $company)
    {
        $this->authorize('view', $company);
        $data = $this->companyService->getCompany($company);
        return $this->successMessage('Successfully retrieved company', ['company' => $data], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $this->authorize('update', $company);
        $validatedData = $request->validated();
        $updatedCompany = $this->companyService->update($company, $validatedData);
        return $this->successMessage('Successfully updated company', ['company' => $updatedCompany], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $this->authorize('delete', $company);
        $this->companyService->destroy($company);
        return $this->successMessage('Successfully deleted company', [], 200);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(Company $company)
    {
        $this->authorize('restore', $company);
        $data = $this->companyService->restore($company);
        return $this->successMessage('Successfully restored company', ['company' => $data], 200);
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete(Company $company)
    {
        $this->authorize('forceDelete', $company);
        $this->companyService->forceDeleteTrashedCompany($company);
        return $this->successMessage('Successfully force deleted company', [], 202);
    }

    /**
     * Get all trashed companies
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTrashedCompanies(Request $request)
    {
        $this->authorize('viewAnyTrashed', Company::class);
        $filters = $request->only(['owner_id', 'name', 'subdomain', 'is_active']);
        $trashedCompanies = $this->companyService->getAllTrashedCompanies($filters);
        return $this->successMessage('Successfully retrieved trashed companies', ['companies' => $trashedCompanies], 200);
    }

    /**
     * Get trashed company
     * @param Company $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrashedCompany(Company $company)
    {
        $this->authorize('viewTrashed', $company);
        $trashedCompany = $this->companyService->getTrashedCompany($company);
        return $this->successMessage('Successfully retrieved trashed company', ['company' => $trashedCompany], 200);
    }

    /**
     * Force delete all trashed companies
     * @return \Illuminate\Http\JsonResponse
     */
    public  function forceDeleteAllTrashedCompanies()
    {
        $this->authorize('forceDeleteAny', Company::class);
        $this->companyService->forceDeleteAllTrashedCompanies();
        return $this->successMessage('Successfully force deleted all trashed companies', [], 202);
    }

    /**
     * Restore all trashed companies
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreAllTrashedCompanies()
    {
        $this->authorize('restoreAny', Company::class);
        $this->companyService->restoreAllTrashedCompanies();
        return $this->successMessage('Successfully restored all trashed companies', [], 200);
    }
}
