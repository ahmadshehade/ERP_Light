<?php

namespace Tests\Feature\Services;

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Central\Models\Company;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Models\Tenant;
use Modules\Central\Services\Tenant\TenantProvisioningService;
use Modules\Tenant\Models\TenantUser;
use RuntimeException;
use Tests\TestCase;

class TenantProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TenantProvisioningService $service;

    protected array $createdTenants = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TenantProvisioningService::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdTenants as $tenant) {
            try {
                if ($tenant->exists) {
                    $tenant->delete();
                }
            } catch (\Throwable $e) {
                // Ignore cleanup errors so they don't hide the actual test failure.
            }
        }

        parent::tearDown();
    }

    /**
     * Create a central user.
     */
    protected function createUser(): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Create a company while respecting the guarded owner_id attribute.
     */
    protected function createCompany(User $owner): Company
    {
        $company = new Company();

        $company->forceFill([
            'name' => [
                'en' => 'Test Company',
                'ar' => 'شركة اختبار',
            ],
            'subdomain' => fake()->unique()->slug(),
            'owner_id' => $owner->id,
            'max_users' => 10,
            'is_active' => true,
        ]);

        $company->save();

        return $company->fresh();
    }

    /**
     * Create a subscription price required by the subscription.
     */
    protected function createSubscriptionPrice(): SubscriptionPrice
    {
        $plan = SubscriptionPlan::create([
            'name' => [
                'en' => 'Test Plan',
                'ar' => 'خطة اختبار',
            ],
            'description' => [
                'en' => 'Test subscription plan',
                'ar' => 'خطة اشتراك للاختبار',
            ],
            'is_active' => true,
        ]);

        return SubscriptionPrice::create([
            'plan_id' => $plan->id,
            'price' => 10.00,
            'interval' => 'month',
        ]);
    }

    /**
     * Create a subscription for a company.
     */
    protected function createSubscription(Company $company): Subscription
    {
        $price = $this->createSubscriptionPrice();

        return Subscription::create([
            'company_id' => $company->id,
            'price_id' => $price->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Register a tenant for cleanup.
     */
    protected function trackTenant(Tenant $tenant): Tenant
    {
        $this->createdTenants[] = $tenant;

        return $tenant;
    }

    /**
     * Get the tenant database name.
     */
    protected function getTenantDatabaseName(Tenant $tenant): string
    {
        /*
         * Stancl Tenancy normally stores the generated database
         * name in tenancy_db_name.
         */
        $database = $tenant->getAttribute('tenancy_db_name');

        if ($database) {
            return $database;
        }

        /*
         * Fallback for projects where the database name is stored
         * differently.
         */
        $database = $tenant->getAttribute('database');

        if ($database) {
            return $database;
        }

        return config('tenancy.database.prefix', 'tenant')
            . $tenant->id
            . config('tenancy.database.suffix', '');
    }

    /*
    |--------------------------------------------------------------------------
    | Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_throws_exception_when_subscription_company_is_missing(): void
    {
        $subscription = new Subscription();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Subscription company not found.');

        $this->service->provision($subscription);
    }

    public function test_it_provisions_tenant_successfully(): void
    {
        $owner = $this->createUser();

        $company = $this->createCompany($owner);

        $subscription = $this->createSubscription($company);

        $tenant = $this->service->provision($subscription);

        $this->trackTenant($tenant);

        /*
         * Tenant must exist.
         */
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'company_id' => $company->id,
        ]);

        /*
         * Tenant must have a domain.
         */
        $this->assertDatabaseHas('domains', [
            'tenant_id' => $tenant->id,
            'domain' => $company->subdomain . '.' . config('app.domain'),
        ]);

        /*
         * Verify tenant database and seeded data.
         */
        $tenant->run(function () use ($owner) {

            /*
             * Tenant migrations must have created tenant_users.
             */
            $this->assertTrue(
                DB::connection('tenant')
                    ->getSchemaBuilder()
                    ->hasTable('tenant_users')
            );

            /*
             * Owner must have been created inside tenant_users.
             */
            $tenantUser = TenantUser::where(
                'user_id',
                $owner->id
            )->first();

            $this->assertNotNull($tenantUser);

            $this->assertTrue(
                (bool) $tenantUser->is_active
            );

            /*
             * Owner role must be assigned.
             */
            $this->assertTrue(
                $tenantUser->hasRole(NameOfRoles::Owner->value)
            );
        });
    }

    public function test_it_returns_existing_tenant_when_company_has_already_been_provisioned(): void
    {
        $owner = $this->createUser();

        $company = $this->createCompany($owner);

        $subscription = $this->createSubscription($company);

        /*
         * First provisioning.
         */
        $firstTenant = $this->service->provision($subscription);

        $this->trackTenant($firstTenant);

        /*
         * Second provisioning for the same company.
         */
        $secondTenant = $this->service->provision($subscription);

        /*
         * It must return the same tenant.
         */
        $this->assertSame(
            $firstTenant->id,
            $secondTenant->id
        );

        /*
         * Only one tenant should belong to this company.
         */
        $this->assertSame(
            1,
            Tenant::where('company_id', $company->id)->count()
        );

        /*
         * Only one domain should exist.
         */
        $this->assertSame(
            1,
            $firstTenant->domains()->count()
        );

        /*
         * Owner must still exist only once in tenant_users.
         */
        $firstTenant->run(function () use ($owner) {

            $this->assertSame(
                1,
                TenantUser::where('user_id', $owner->id)->count()
            );

            $tenantUser = TenantUser::where(
                'user_id',
                $owner->id
            )->first();

            $this->assertNotNull($tenantUser);

            $this->assertTrue(
                $tenantUser->hasRole(NameOfRoles::Owner->value)
            );
        });
    }

    public function test_it_deletes_tenant_successfully(): void
    {
        $owner = $this->createUser();

        $company = $this->createCompany($owner);

        $subscription = $this->createSubscription($company);

        $tenant = $this->service->provision($subscription);

        $this->trackTenant($tenant);

        $tenantId = $tenant->id;

        /*
         * Make sure tenant exists before deletion.
         */
        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
        ]);

        /*
         * Delete tenant.
         */
        $this->service->delete($tenant);

        /*
         * Tenant record should be gone.
         */
        $this->assertDatabaseMissing('tenants', [
            'id' => $tenantId,
        ]);

        /*
         * Remove from cleanup list because it has already
         * been deleted.
         */
        $this->createdTenants = array_filter(
            $this->createdTenants,
            fn(Tenant $createdTenant) =>
            $createdTenant->id !== $tenantId
        );
    }

    public function test_it_creates_only_one_tenant_for_company(): void
    {
        $owner = $this->createUser();

        $company = $this->createCompany($owner);

        $subscription = $this->createSubscription($company);

        $firstTenant = $this->service->provision($subscription);

        $this->trackTenant($firstTenant);

        $secondTenant = $this->service->provision($subscription);

        $this->assertSame(
            $firstTenant->id,
            $secondTenant->id
        );

        $this->assertDatabaseCount(
            'tenants',
            1
        );
    }

    public function test_it_creates_owner_tenant_user_with_owner_role(): void
    {
        $owner = $this->createUser();

        $company = $this->createCompany($owner);

        $subscription = $this->createSubscription($company);

        $tenant = $this->service->provision($subscription);

        $this->trackTenant($tenant);

        $tenant->run(function () use ($owner) {

            $tenantUser = TenantUser::where(
                'user_id',
                $owner->id
            )->first();

            $this->assertNotNull($tenantUser);

            $this->assertTrue(
                $tenantUser->is_active
            );

            $this->assertTrue(
                $tenantUser->hasRole(NameOfRoles::Owner->value)
            );
        });
    }

    public function test_it_creates_tenant_domain_from_company_subdomain(): void
    {
        $owner = $this->createUser();

        $company = $this->createCompany($owner);

        $subscription = $this->createSubscription($company);

        $tenant = $this->service->provision($subscription);

        $this->trackTenant($tenant);

        $expectedDomain =
            $company->subdomain . '.' . config('app.domain');

        $this->assertDatabaseHas('domains', [
            'tenant_id' => $tenant->id,
            'domain' => $expectedDomain,
        ]);
    }
}
