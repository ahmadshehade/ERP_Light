<?php

namespace Modules\Central\Http\Controllers\Api\V1\Central;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Central\Services\SubscriptionPlanService;
use Modules\Central\Http\Requests\Api\V1\Central\StoreSubscriptionPlanRequest;
use Modules\Central\Http\Requests\Api\V1\Central\UpdateSubscriptionPlanRequest;
use Modules\Central\Models\SubscriptionPlan;

class SubscriptionPlanController extends Controller
{

    use AuthorizesRequests;
    /**
     * Create a new controller instance.
     *
     */
    public  function __construct(public SubscriptionPlanService $subscriptionPlanService) {}
    /**
     * Display a listing of the resource.
     * @Prama Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', SubscriptionPlan::class);
        $filters = $request->only(['name', 'description', 'is_active']);
        $plans = $this->subscriptionPlanService->getAllPlans($filters);
        return $this->successMessage('Successfully retrieved subscription plans', ['plans' => $plans], 200);
    }

    /**
     * Store a newly created resource in storage.
     * @Prama StoreSubscriptionPlanRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSubscriptionPlanRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', SubscriptionPlan::class);
        $plan = $this->subscriptionPlanService->store($request->validated());
        return $this->successMessage('Successfully created subscription plan', ['plan' => $plan], 201);
    }

    /**
     * Show the specified resource.
     * @Prama SubscriptionPlan $subscriptionPlan
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(SubscriptionPlan $subscriptionPlan): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $subscriptionPlan);
        $data = $this->subscriptionPlanService->get($subscriptionPlan);
        return $this->successMessage('Successfully retrieved subscription plan', ['plan' => $data], 200);
    }

    /**
     * Update the specified resource in storage.
     * @Prama UpdateSubscriptionPlanRequest $request
     * @Prama SubscriptionPlan $subscriptionPlan
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $subscriptionPlan);
        $plan = $this->subscriptionPlanService->update($subscriptionPlan, $request->validated());
        return $this->successMessage('Successfully updated subscription plan', ['plan' => $plan], 200);
    }

    /**
     * Remove the specified resource from storage.
     * @Prama SubscriptionPlan $subscriptionPlan
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SubscriptionPlan $subscriptionPlan): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $subscriptionPlan);
        $this->subscriptionPlanService->delete($subscriptionPlan);
        return $this->successMessage('Successfully deleted subscription plan', [], 200);
    }

    /**
     * Restore the specified resource from storage.
     * @Prama SubscriptionPlan $subscriptionPlan
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(SubscriptionPlan $subscriptionPlan): \Illuminate\Http\JsonResponse
    {
        $this->authorize('restore', $subscriptionPlan);
        $plan = $this->subscriptionPlanService->restore($subscriptionPlan);
        return $this->successMessage('Successfully restored subscription plan', ['plan' => $plan], 200);
    }
    /**
     * Remove the specified resource from storage.
     * @Prama SubscriptionPlan $subscriptionPlan
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(SubscriptionPlan $subscriptionPlan): \Illuminate\Http\JsonResponse
    {
        $this->authorize('forceDelete', $subscriptionPlan);
        $this->subscriptionPlanService->forceDelete($subscriptionPlan);
        return $this->successMessage('Successfully deleted subscription plan', [], 200);
    }
    /**
     * Restore the specified resource from storage.
     * @Prama SubscriptionPlan $subscriptionPlan
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreAll(): \Illuminate\Http\JsonResponse
    {
        $this->authorize('restoreAll', SubscriptionPlan::class);
        $this->subscriptionPlanService->restoreAll();
        return $this->successMessage('Successfully restored subscription plans', [], 200);
    }
    /**
     * Remove the specified resource from storage.
     * @Prama SubscriptionPlan $subscriptionPlan
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDeleteAll(): \Illuminate\Http\JsonResponse
    {
        $this->authorize('forceDeleteAll', SubscriptionPlan::class);
        $this->subscriptionPlanService->forceDeleteAll();
        return $this->successMessage('Successfully deleted subscription plans', [], 200);
    }

    /**
     * Summary of viewTrashedPlans
     *  @prama Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function viewTrashedPlans(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewTrashedPlans', SubscriptionPlan::class);
        $filters = $request->only([
            'name',
            'description',
            'is_active'
        ]);
        $plans = $this->subscriptionPlanService->viewTrashedPlans($filters);
        return $this->successMessage('Successfully retrieved trashed subscription plans', ['plans' => $plans], 200);
    }

    /**
     * Summary of viewTrashedPlan
     * @param SubscriptionPlan $subscriptionPlan
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewTrashedPlan(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->authorize('viewTrashedPlan', $subscriptionPlan);
        $plan = $this->subscriptionPlanService->viewTrashedPlan($subscriptionPlan);
        return $this->successMessage('Successfully retrieved trashed subscription plan', ['plan' => $plan], 200);
    }
}
