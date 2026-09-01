<?php


use Illuminate\Support\Facades\Route;
use Modules\Central\Http\Controllers\Api\V1\Central\CompanyController;
use Modules\Central\Http\Controllers\Api\V1\Central\PaymentController;
use Modules\Central\Http\Controllers\Api\V1\Central\SubscriptionController;
use Modules\Central\Http\Controllers\Api\V1\Central\SubscriptionPlanController;
use Modules\Central\Http\Controllers\Api\V1\Central\SubscriptionPriceController;



use Modules\Central\Http\Controllers\Api\V1\Central\StripeWebhookController;

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);









Route::middleware(['auth:sanctum'])->prefix('v1/central')->group(function () {



    ################################  Subscription Plan  ####################################

    Route::prefix('subscriptionplans')->group(function () {

        Route::get('/trashed', [SubscriptionPlanController::class, 'viewTrashedPlans']);
        Route::get('/{subscriptionPlan}/trashed', [SubscriptionPlanController::class, 'viewTrashedPlan'])
            ->withTrashed();
        Route::post('/{subscriptionPlan}/restore', [SubscriptionPlanController::class, 'restore'])
            ->withTrashed();
        Route::delete('/{subscriptionPlan}/force-delete', [SubscriptionPlanController::class, 'forceDelete'])
            ->withTrashed();
        Route::post('/restore-all', [SubscriptionPlanController::class, 'restoreAll'])->withTrashed();
        Route::delete('/force-delete-all', [SubscriptionPlanController::class, 'forceDeleteAll'])->withTrashed();

        Route::get('/', [SubscriptionPlanController::class, 'index']);
        Route::get('/{subscriptionPlan}', [SubscriptionPlanController::class, 'show']);
        Route::post('/', [SubscriptionPlanController::class, 'store']);
        Route::put('/{subscriptionPlan}', [SubscriptionPlanController::class, 'update']);
        Route::delete('/{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy']);
    });


    ################################  Subscription Price  ####################################

    Route::prefix('subscriptionprices')->group(function () {

        Route::get('/trashed', [SubscriptionPriceController::class, 'getAllTrashedSubscriptionPrices']);
        Route::get('/{subscriptionPrice}/trashed', [SubscriptionPriceController::class, 'getTrashedSubscriptionPrice'])
            ->withTrashed();
        Route::post('/{subscriptionPrice}/restore', [SubscriptionPriceController::class, 'restore'])
            ->withTrashed();
        Route::delete('/{subscriptionPrice}/force-delete', [SubscriptionPriceController::class, 'forceDelete'])
            ->withTrashed();
        Route::post('/restore-all', [SubscriptionPriceController::class, 'restoreAll'])->withTrashed();
        Route::delete('/force-delete-all', [SubscriptionPriceController::class, 'forceDeleteAll'])
            ->withTrashed();

        Route::get('/', [SubscriptionPriceController::class, 'index']);
        Route::get('/{subscriptionPrice}', [SubscriptionPriceController::class, 'show']);
        Route::post('/', [SubscriptionPriceController::class, 'store']);
        Route::put('/{subscriptionPrice}', [SubscriptionPriceController::class, 'update']);
        Route::delete('/{subscriptionPrice}', [SubscriptionPriceController::class, 'destroy']);
    });

    ################################  Company  ############################################

    Route::prefix('companies')->group(function () {

        Route::get('/trashed', [CompanyController::class, 'getAllTrashedCompanies']);
        Route::get('/{company}/trashed', [CompanyController::class, 'getTrashedCompany'])
            ->withTrashed();
        Route::post('/{company}/restore', [CompanyController::class, 'restore'])
            ->withTrashed();
        Route::delete('/{company}/force-delete', [CompanyController::class, 'forceDelete'])
            ->withTrashed();
        Route::post('/restore-all', [CompanyController::class, 'restoreAllTrashedCompanies'])
            ->withTrashed();
        Route::delete('/force-delete-all', [CompanyController::class, 'forceDeleteAllTrashedCompanies'])
            ->withTrashed();
        Route::get('/', [CompanyController::class, 'index']);
        Route::get('/{company}', [CompanyController::class, 'show']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::post('/{company}', [CompanyController::class, 'update']);
        Route::delete('/{company}', [CompanyController::class, 'destroy']);
    });

    #######################################  Subscription  ############################################

    Route::prefix('subscriptions')->group(function () {
        Route::get('/trashed', [SubscriptionController::class, 'getTrashedSubscriptions']);
        Route::get('/{subscription}/trashed', [SubscriptionController::class, 'getTrashedSubscription'])
            ->withTrashed();
        Route::post('/{subscription}/restore', [SubscriptionController::class, 'restore'])
            ->withTrashed();
        Route::delete('/{subscription}/force-delete', [SubscriptionController::class, 'forceDelete'])
            ->withTrashed();
        Route::post('/restore-all', [SubscriptionController::class, 'restoreAll'])
            ->withTrashed();
        Route::delete('/force-delete-all', [SubscriptionController::class, 'forceDeleteAll'])
            ->withTrashed();
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::get('/{subscription}', [SubscriptionController::class, 'show']);
        Route::post('/', [SubscriptionController::class, 'store']);
        Route::put('/{subscription}', [SubscriptionController::class, 'update']);
        Route::delete('/{subscription}', [SubscriptionController::class, 'destroy']);
        Route::post('/{subscription}/renew', [SubscriptionController::class, 'reNew']);
    });

    //###############################  Pyament  ######################################################
    Route::prefix('/payments')->group(function () {
        Route::get('/trashed', [PaymentController::class, 'getAllTrashed']);
        Route::get('/{payment}/trashed', [PaymentController::class, 'getTrashed'])
            ->withTrashed();
        Route::post('/{payment}/restore', [PaymentController::class, 'restore'])
            ->withTrashed();
        Route::delete('/{payment}/force-delete', [PaymentController::class, 'forceDelete'])
            ->withTrashed();
        Route::post('/restore-all', [PaymentController::class, 'restoreAll'])
            ->withTrashed();
        Route::delete('/force-delete-all', [PaymentController::class, 'forceDeleteAll'])
            ->withTrashed();
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('/{payment}', [PaymentController::class, 'show']);
        Route::put('/{payment}', [PaymentController::class, 'update']);
        Route::delete('/{payment}', [PaymentController::class, 'destroy']);

        Route::post('/{payment}/cancel', [PaymentController::class, 'cancel']);
        Route::post('/{payment}/refund', [PaymentController::class, 'refund']);
        Route::post('/{payment}/retry', [PaymentController::class, 'retry']);
    });
});
