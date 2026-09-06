<?php

namespace Modules\Central\Http\Controllers\Api\V1\Central;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Central\Http\Requests\Api\V1\Central\SubscribePrice\StoreSubscriptionPriceRequest;
use Modules\Central\Http\Requests\Api\V1\Central\SubscribePrice\UpdateSubscriptionPriceRequest;
use Modules\Central\Services\SubscriptionPriceService;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Transformers\SubscriptionPriceResource;

class SubscriptionPriceController extends Controller
{

    use AuthorizesRequests;
    public function __construct(public SubscriptionPriceService $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', SubscriptionPrice::class);
        $filters = $request->only(['plan_id', 'is_active', 'stripe_price_id', 'price', 'interval']);
        $prices = $this->service->getAll($filters);
        return $this->successMessage('Successfully Retrieved All Subscription Price', SubscriptionPriceResource::collection($prices), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionPriceRequest $request)
    {
        $this->authorize('create', SubscriptionPrice::class);
        $price = $this->service->store($request->validated());
        return $this->successMessage('Successfully Created Subscription Price', SubscriptionPriceResource::make($price), 201);
    }

    /**
     * Show the specified resource.
     */
    public function show(SubscriptionPrice $subscriptionPrice)
    {
        $this->authorize('view', $subscriptionPrice);
        $price = $this->service->get($subscriptionPrice);
        return $this->successMessage('Successfully Retrieved Subscription Price', SubscriptionPriceResource::make($price), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubscriptionPriceRequest $request, SubscriptionPrice $subscriptionPrice)
    {
        $this->authorize('update', $subscriptionPrice);
        $price = $this->service->update($subscriptionPrice, $request->validated());
        return $this->successMessage('Successfully Updated Subscription Price', SubscriptionPriceResource::make($price), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubscriptionPrice $subscriptionPrice)
    {
        $this->authorize('delete', $subscriptionPrice);
        $this->service->delete($subscriptionPrice);
        return $this->successMessage('Successfully Deleted Subscription Price', [], 200);
    }

    /**
     * Summary of forceDelete
     * @param SubscriptionPrice $subscriptionPrice
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(SubscriptionPrice $subscriptionPrice)
    {
        $this->authorize('forceDelete', $subscriptionPrice);
        $this->service->forceDeleteSubscriptionPrice($subscriptionPrice);
        return $this->successMessage('Successfully Force Deleted Subscription Price', [], 200);
    }

    /**
     * Summary of restore
     * @param SubscriptionPrice $subscriptionPrice
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(SubscriptionPrice $subscriptionPrice)
    {
        $this->authorize('restore', $subscriptionPrice);
        $data = $this->service->restoreSubscriptionPrice($subscriptionPrice);
        return $this->successMessage('Successfully Restored Subscription Price', SubscriptionPriceResource::make($data), 200);
    }

    /**
     * Summary of forceDeleteAll
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDeleteAll()
    {
        $this->authorize('forceDeleteAll', SubscriptionPrice::class);
        $this->service->forceDeleteSubscriptionPrices();
        return $this->successMessage('Successfully Force Deleted All Subscription Price', [], 200);
    }

    /**
     * Summary of restoreAll
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreAll()
    {
        $this->authorize('restoreAll', SubscriptionPrice::class);
        $this->service->restoreSubscriptionPrices();
        return $this->successMessage('Successfully Restored All Subscription Price', [], 200);
    }


    /**
     * Summary of getAllTrashedSubscriptionPrices
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTrashedSubscriptionPrices(Request $request)
    {
        $this->authorize('viewAnyTrashedPrices', SubscriptionPrice::class);
        $filters = $request->only(['plan_id', 'is_active', 'stripe_price_id', 'price', 'interval']);
        $prices = $this->service->getTrashedSubscriptionPrices($filters);
        return $this->successMessage('Successfully Retrieved All Trashed Subscription Price', SubscriptionPriceResource::collection($prices), 200);
    }


    /**
     * Display the specified resource.
     * @param  \Modules\Central\Models\SubscriptionPrice  $subscriptionPrice
     * @return \Illuminate\Http\Response
     */
    public function getTrashedSubscriptionPrice(SubscriptionPrice $subscriptionPrice)
    {
        $this->authorize('viewTrashed', $subscriptionPrice);
        $price = $this->service->getTrashedSubscriptionPrice($subscriptionPrice);
        return $this->successMessage('Successfully Retrieved Trashed Subscription Price', SubscriptionPriceResource::make($price), 200);
    }
}
