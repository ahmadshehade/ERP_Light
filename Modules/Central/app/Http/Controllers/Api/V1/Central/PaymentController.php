<?php

namespace Modules\Central\Http\Controllers\Api\V1\Central;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Modules\Central\Http\Requests\Api\V1\Central\Payments\UpdatePaymentRequest;
use Modules\Central\Models\Payment;
use Modules\Central\Services\Payments\PaymentLifeSycle;
use Modules\Central\Services\Payments\PaymentService;
use Modules\Central\Transformers\PaymentResource;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(public PaymentLifeSycle $lifeSycle, public PaymentService $paymentService) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        $filters = $request->only(['amount', 'status', 'paid_at', 'gateway', 'subscription_id']);
        $payments = $this->paymentService->getAll($filters);
        return $this->successMessage('Successfully retrieved payments', PaymentResource::collection($payments), 200);
    }

    /**
     * Show the specified resource.
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);
        $data = $this->paymentService->get($payment);
        return $this->successMessage('Successfully retrieved payment', PaymentResource::make($data), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);
        $data = $this->paymentService->update($payment, $request->validated());
        return $this->successMessage('Successfully updated payment', PaymentResource::make($data), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);
        $data = $this->paymentService->destroy($payment);
        return $this->successMessage('Successfully delete payment', [], 200);
    }

    /**
     * restore payment
     * @prama Payment $payment
     * @return JsonResponse
     */
    public function restore(Payment $payment): JsonResponse
    {
        $this->authorize('restore', $payment);
        $data = $this->paymentService->restore($payment);
        return $this->successMessage('Successfully restore payment', PaymentResource::make($data), 200);
    }

    /**
     * forceDelete payment
     * @prama Payment $payment
     * @return JsonResponse
     */
    public function forceDelete(Payment $payment): JsonResponse
    {
        $this->authorize('forceDelete', $payment);
        $this->paymentService->forceDelete($payment);
        return $this->successMessage('Successfully forceDelete payment', [], 200);
    }

    /**
     * getTrashed payments
     * @prama Request $request
     * @return JsonResponse
     */
    public function getAllTrashed(Request $request): JsonResponse
    {
        $this->authorize('getAnyTrashed', Payment::class);
        $filters = $request->only(['amount', 'status', 'paid_at', 'gateway', 'subscription_id']);
        $trashedPayments = $this->paymentService->getAllTrashed($filters);
        return $this->successMessage('Successfully retrieved trasshed payments', PaymentResource::collection($trashedPayments), 200);
    }

    /**
     * get trashed payment
     * @prama Payment Payment
     * @return JsonResponse
     */
    public function getTrashed(Payment $payment): JsonResponse
    {
        $this->authorize('getTrashed', $payment);
        $data = $this->paymentService->getTrashed($payment);
        return $this->successMessage('Successfully retrieved trashed payment', PaymentResource::make($data), 200);
    }

    /**
     * restore All trashed payments
     * @prama Payment $payment
     * @return JsonResponse
     */
    public function restoreAll(): JsonResponse
    {
        $this->authorize('restoreAll', Payment::class);
        $this->paymentService->restoreAll();
        return $this->successMessage('Successfully rstore all trashed payment', [], 200);
    }

    /**
     * forceDelete All trashed payments
     * @return JsonResponse
     */
    public function forceDeleteAll(): JsonResponse
    {
        $this->authorize('forceDeleteAll', Payment::class);
        $this->paymentService->forceDeleteAll();
        return $this->successMessage('Successfully forceDelete all trashed payments', [], 200);
    }

    /**
     *  pay payment
     * @prama Payment $payment
     * @return JsonResponse
     */
    public function pay(Payment $payment): JsonResponse
    {
        $pay = $this->lifeSycle->pay($payment);
        return $this->successMessage('Successfully pay payment', PaymentResource::make($pay), 200);
    }

    /**
     * cancel payment
     * @prama Payment $payment
     * @return JsonResponse
     */
    public function cancel(Payment $payment): JsonResponse
    {
        $this->authorize('cancel', $payment);
        $cancel = $this->lifeSycle->cancel($payment);
        return $this->successMessage('Successfully cancel payment', PaymentResource::make($cancel), 200);
    }

    /**
     * retry payment
     * @prama Payment $payment
     * @return JsonResponse
     */
    public function retry(Payment $payment): JsonResponse
    {
        $this->authorize('retry', $payment);
        $retry = $this->lifeSycle->retry($payment);
        return $this->successMessage('Successfully retry payment', PaymentResource::make($retry), 200);
    }

    /**
     * refund payment
     * @prama Payment $payment
     * @return JsonResponse
     */
    public function refund(Payment $payment): JsonResponse
    {
        $this->authorize('refund', $payment);
        $refund = $this->lifeSycle->refund($payment);
        return $this->successMessage('Successfully refund paymnet', PaymentResource::make($refund), 200);
    }

    /**
     * fail payment
     * @prama Payment $payment
     * @return JsonResponse
     */
    public function fail(Payment $payment, Request $request)
    {

        $reason = $request->input('reason');
        $fail = $this->lifeSycle->fail($payment, $reason);
        return $this->successMessage('Successfully fail payment', PaymentResource::make($fail), 200);
    }
}
