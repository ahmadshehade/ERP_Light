<?php

namespace Modules\Central\Http\Controllers\Api\V1\Central;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Central\Services\SubscriptionService;
use Modules\Central\Http\Requests\Api\V1\Central\Subscriptions\ReNewSubscriptionRequest;
use Modules\Central\Http\Requests\Api\V1\Central\Subscriptions\StoreSubscriptionRequest;
use Modules\Central\Http\Requests\Api\V1\Central\Subscriptions\UpdateSubscriptionRequest;
use Modules\Central\Models\Subscription;

class SubscriptionController extends Controller
{
    use AuthorizesRequests;
    public  function __construct(public SubscriptionService $subscriptionService) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Subscription::class);
        $filters = $request->only(['price_id', 'company_id', 'status']);
        $subscriptions = $this->subscriptionService->getAll($filters);
        return $this->successMessage('Successfully Retrieved Subscriptions', ['subscriptions' => $subscriptions], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionRequest   $request): JsonResponse
    {
        $this->authorize('create', Subscription::class);
        $subscription = $this->subscriptionService->store($request->validated());
        return $this->successMessage('Successfully Created Subscription', ['subscription' => $subscription], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show(Subscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);
        $data = $this->subscriptionService->getSubscription($subscription);
        return $this->successMessage('Successfully Retrieved Subscription', ['subscription' => $data], 200);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $this->authorize('update', $subscription);
        $data = $this->subscriptionService->update($subscription, $request->validated());
        return $this->successMessage('Successfully Updated Subscription', ['subscription' => $data], 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription): JsonResponse
    {
        $this->authorize('delete', $subscription);
        $this->subscriptionService->destroy($subscription);
        return $this->successMessage('Successfully Deleted Subscription', [], 200);
    }
    /**
     * Restore the specified resource from storage.
     * @param $subscription
     * @return JsonResponse
     *
     */
    public function restore(Subscription $subscription): JsonResponse
    {
        $this->authorize('restore', $subscription);
        $data = $this->subscriptionService->restore($subscription);
        return $this->successMessage('Successfully Restored Subscription', ['subscription' => $data], 200);
    }
    /**
     * Remove the specified resource from storage.
     * @param $subscription
     * @return JsonResponse
     */
    public  function forceDelete(Subscription $subscription): JsonResponse
    {
        $this->authorize('forceDelete', $subscription);
        $this->subscriptionService->forceDelete($subscription);
        return $this->successMessage('Successfully Deleted Subscription', [], 200);
    }
    /**
     * Display a listing of the trashed resource.
     * @return JsonResponse
     *
     */
    public function getTrashedSubscriptions(Request $request): JsonResponse
    {
        $this->authorize('getAllTrashed', Subscription::class);
        $filters = $request->only(['price_id', 'company_id', 'status']);
        $subscriptions = $this->subscriptionService->getAllTrashed($filters);
        return $this->successMessage('Successfully Retrieved Subscriptions', ['subscriptions' => $subscriptions], 200);
    }
    /**
     * Display the specified trashed resource.
     * @param $subscription
     * @return JsonResponse
     */
    public function getTrashedSubscription(Subscription $subscription): JsonResponse
    {
        $this->authorize('getTrashed', $subscription);
        $data = $this->subscriptionService->getTrashed($subscription);
        return $this->successMessage('Successfully Retrieved Subscription', ['subscription' => $data], 200);
    }
    /**
     * Summary of forceDeleteAllSubscriptions
     * @return JsonResponse
     */
    public function forceDeleteAll(): JsonResponse
    {
        $this->authorize('forceDeleteAll', Subscription::class);
        $this->subscriptionService->destroyAllTrashed();
        return $this->successMessage('Successfully Deleted Subscriptions', [], 200);
    }
    /**
     * Summary of restoreAllSubscriptions
     * @return JsonResponse
     */
    public  function restoreAll(): JsonResponse
    {
        $this->authorize('restoreAll', Subscription::class);
        $this->subscriptionService->restoreAll();
        return $this->successMessage('Successfully Restored Subscriptions', [], 200);
    }

    public function reNew(Subscription $subscription, ReNewSubscriptionRequest $request)
    {
        $this->authorize('reNew', $subscription);
        $data = $this->subscriptionService->renew($subscription, $request->validated());
        return $this->successMessage('Successfully Renewed Subscription ', ['subscription' => $data], 200);
    }
}
