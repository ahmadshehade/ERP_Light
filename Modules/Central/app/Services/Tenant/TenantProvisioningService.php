<?php

namespace Modules\Central\Services\Tenant;

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use RuntimeException;

class TenantProvisioningService
{
    public function provision(Subscription $subscription): Tenant
    {
        $subscription->loadMissing('company');

        $company = $subscription->company;

        if (!$company) {
            throw new RuntimeException(
                'Subscription company not found.'
            );
        }

        $owner = User::on('mysql')
            ->find($company->owner_id);

        if (!$owner) {
            throw new RuntimeException(
                'Company owner not found.'
            );
        }

        $tenant = Tenant::firstOrCreate([
            'company_id' => $company->id,
        ]);

        $tenant->domains()->firstOrCreate([
            'domain' => $company->subdomain . '.' . config('app.domain'),
        ]);

        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id],
        ]);

        $tenant->run(function () {
            $seeder = app(
                \Database\Seeders\Tenant\TenantDatabaseSeeder::class
            );

            $seeder->run();
        });

        $tenant->run(function () use ($owner) {

            $tenantUser = TenantUser::firstOrCreate(
                [
                    'user_id' => $owner->id,
                ],
                [
                    'is_active' => true,
                ]
            );

            $tenantUser->assignRole(
                NameOfRoles::Owner->value
            );
        });

        return $tenant;
    }


    /**
     * Delete a tenant
     */
    public function delete(Tenant $tenant)
    {
        $tenant->delete();
    }
}
