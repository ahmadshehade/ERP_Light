<?php

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Central\Jobs\DeleteCompanyJob;
use Modules\Central\Models\Company;

uses(DatabaseTransactions::class);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function createCompanyManagementAdmin(): User
{
    $user = User::create([
        'name' => 'Company Management Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->assignRole(NameOfRoles::SuperAdmin->value);

    return $user;
}

function createCompanyTestUser(): User
{
    return User::create([
        'name' => 'Company Test User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('P@ssw0rd123'),
    ]);
}

function createTestCompany(User $owner): Company
{
    $company = new Company();

    $company->owner_id = $owner->id;

    $company->name = [
        'en' => 'Test Company ' . fake()->unique()->numberBetween(1000, 9999),
        'ar' => 'شركة اختبار ' . fake()->unique()->numberBetween(1000, 9999),
    ];

    $company->subdomain = 'company-' . fake()->unique()->numberBetween(1000, 999999);

    $company->max_users = 100;
    $company->is_active = true;
    $company->database = 'tenant-' . fake()->uuid();

    $company->save();

    return $company;
}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

test('unauthenticated user cannot access companies', function () {

    $response = $this->getJson('/api/v1/central/companies');

    $response->assertStatus(401);
});


/*
|--------------------------------------------------------------------------
| Get Companies
|--------------------------------------------------------------------------
*/

test('admin can get all companies', function () {

    $admin = createCompanyManagementAdmin();

    createTestCompany($admin);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/central/companies');

    $response->assertStatus(200);
});


test('admin can get a specific company', function () {

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/central/companies/{$company->id}");

    $response->assertStatus(200);
});


test('company owner can get their own company', function () {

    $owner = createCompanyTestUser();

    $company = createTestCompany($owner);

    $response = $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/central/companies/{$company->id}");

    $response->assertStatus(200);
});


test('user cannot get another user company', function () {

    $user = createCompanyTestUser();

    $owner = createCompanyTestUser();

    $company = createTestCompany($owner);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/central/companies/{$company->id}");


    $response->assertStatus(403);
});


/*
|--------------------------------------------------------------------------
| Create Company
|--------------------------------------------------------------------------
*/

test('authenticated user can create a company', function () {

    $user = createCompanyTestUser();

    $payload = [
        'name_en' => 'Test Company',
        'name_ar' => 'شركة اختبار',
        'subdomain' => 'test-company',
        'max_users' => 100,
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies', $payload);

    $response->assertStatus(201);

    $company = Company::query()
        ->where('subdomain', 'test-company')
        ->first();

    expect($company)->not->toBeNull()
        ->and($company->owner_id)->toBe($user->id)
        ->and($company->max_users)->toBe(100);
});


test('company owner is taken from authenticated user and cannot be supplied by request', function () {

    $user = createCompanyTestUser();

    $anotherUser = createCompanyTestUser();

    $payload = [
        'name_en' => 'Protected Owner Company',
        'name_ar' => 'شركة المالك المحمي',
        'subdomain' => 'protected-owner-company',
        'max_users' => 100,
        'owner_id' => $anotherUser->id,
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies', $payload);

    $response->assertStatus(201);

    $company = Company::query()
        ->where('subdomain', 'protected-owner-company')
        ->first();

    expect($company->owner_id)
        ->toBe($user->id)
        ->not->toBe($anotherUser->id);
});


/*
|--------------------------------------------------------------------------
| Create Company Validation
|--------------------------------------------------------------------------
*/

test('cannot create company without required fields', function () {

    $user = createCompanyTestUser();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'name.en',
            'name.ar',
            'subdomain',
            'max_users',
        ]);
});


test('cannot create company with existing subdomain', function () {

    $user = createCompanyTestUser();

    $company = createTestCompany($user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies', [
            'name_en' => 'Another Company',
            'name_ar' => 'شركة أخرى',
            'subdomain' => $company->subdomain,
            'max_users' => 100,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'subdomain',
        ]);
});


test('cannot create company with existing english name', function () {

    $user = createCompanyTestUser();

    $company = createTestCompany($user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies', [
            'name_en' => $company->getTranslation('name', 'en'),
            'name_ar' => 'اسم عربي جديد',
            'subdomain' => 'another-company-' . fake()->numberBetween(1000, 9999),
            'max_users' => 100,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'name.en',
        ]);
});


test('cannot create company with existing arabic name', function () {

    $user = createCompanyTestUser();

    $company = createTestCompany($user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies', [
            'name_en' => 'Another English Company',
            'name_ar' => $company->getTranslation('name', 'ar'),
            'subdomain' => 'another-company-' . fake()->numberBetween(1000, 9999),
            'max_users' => 100,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'name.ar',
        ]);
});


test('cannot create company with invalid max users', function () {

    $user = createCompanyTestUser();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies', [
            'name_en' => 'Invalid Users Company',
            'name_ar' => 'شركة عدد مستخدمين خاطئ',
            'subdomain' => 'invalid-users-company',
            'max_users' => 0,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'max_users',
        ]);
});


test('cannot create company with max users greater than 3000', function () {

    $user = createCompanyTestUser();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies', [
            'name_en' => 'Too Many Users Company',
            'name_ar' => 'شركة عدد مستخدمين كبير',
            'subdomain' => 'too-many-users-company',
            'max_users' => 3001,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'max_users',
        ]);
});


test('cannot create company with invalid subdomain', function () {

    $user = createCompanyTestUser();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies', [
            'name_en' => 'Invalid Subdomain Company',
            'name_ar' => 'شركة نطاق خاطئ',
            'subdomain' => 'Invalid_Subdomain',
            'max_users' => 100,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'subdomain',
        ]);
});


/*
|--------------------------------------------------------------------------
| Update Company
|--------------------------------------------------------------------------
*/

test('company owner can update their own company', function () {

    $owner = createCompanyTestUser();

    $company = createTestCompany($owner);

    $response = $this->actingAs($owner, 'sanctum')
        ->patchJson("/api/v1/central/companies/{$company->id}", [
            'name_en' => 'Updated Company',
            'name_ar' => 'شركة محدثة',
            'max_users' => 200,
        ]);

    $response->assertStatus(200);

    $company->refresh();

    expect($company->getTranslation('name', 'en'))
        ->toBe('Updated Company')
        ->and($company->getTranslation('name', 'ar'))
        ->toBe('شركة محدثة')
        ->and($company->max_users)
        ->toBe(200);
});


test('admin can update a company', function () {

    $admin = createCompanyManagementAdmin();

    $companyOwner = createCompanyTestUser();

    $company = createTestCompany($companyOwner);

    $response = $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/central/companies/{$company->id}", [
            'name_en' => 'Admin Updated Company',
            'name_ar' => 'شركة محدثة بواسطة المدير',
            'max_users' => 250,
        ]);

    $response->assertStatus(200);

    $company->refresh();

    expect($company->getTranslation('name', 'en'))
        ->toBe('Admin Updated Company')
        ->and($company->max_users)
        ->toBe(250);
});


test('user cannot update another user company', function () {

    $user = createCompanyTestUser();

    $owner = createCompanyTestUser();

    $company = createTestCompany($owner);

    $response = $this->actingAs($user, 'sanctum')
        ->patchJson("/api/v1/central/companies/{$company->id}", [
            'name_en' => 'Unauthorized Update',
        ]);

    $response->assertStatus(403);
});


test('admin cannot update company with existing subdomain', function () {

    $admin = createCompanyManagementAdmin();

    $company1 = createTestCompany($admin);
    $company2 = createTestCompany($admin);

    $response = $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/central/companies/{$company1->id}", [
            'subdomain' => $company2->subdomain,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'subdomain',
        ]);
});


test('admin cannot update company with existing english name', function () {

    $admin = createCompanyManagementAdmin();

    $company1 = createTestCompany($admin);
    $company2 = createTestCompany($admin);

    $response = $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/central/companies/{$company1->id}", [
            'name_en' => $company2->getTranslation('name', 'en'),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'name.en',
        ]);
});


test('company can be updated without changing existing name and subdomain', function () {

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $oldNameEn = $company->getTranslation('name', 'en');
    $oldNameAr = $company->getTranslation('name', 'ar');
    $oldSubdomain = $company->subdomain;

    $response = $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/central/companies/{$company->id}", [
            'max_users' => 500,
        ]);

    $response->assertStatus(200);

    $company->refresh();

    expect($company->getTranslation('name', 'en'))
        ->toBe($oldNameEn)
        ->and($company->getTranslation('name', 'ar'))
        ->toBe($oldNameAr)
        ->and($company->subdomain)
        ->toBe($oldSubdomain)
        ->and($company->max_users)
        ->toBe(500);
});


/*
|--------------------------------------------------------------------------
| Company Logo
|--------------------------------------------------------------------------
*/

test('admin can upload company logo', function () {

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $photo = UploadedFile::fake()->image('company-logo.jpg', 500, 500);

    $response = $this->actingAs($admin, 'sanctum')
        ->patch("/api/v1/central/companies/{$company->id}", [
            'photo' => $photo,
        ]);

    $response->assertStatus(200);
});


test('cannot upload invalid company logo', function () {

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $file = UploadedFile::fake()->create(
        'company-logo.pdf',
        100,
        'application/pdf'
    );

    $response = $this->actingAs($admin, 'sanctum')
        ->patch("/api/v1/central/companies/{$company->id}", [
            'photo' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'photo',
        ]);
});


/*
|--------------------------------------------------------------------------
| Soft Delete
|--------------------------------------------------------------------------
*/
test('company owner can soft delete their company', function () {

    $owner = createCompanyTestUser();

    $company = createTestCompany($owner);

    $companyId = $company->id;

    $response = $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/central/companies/{$companyId}");

    $response->assertStatus(200);

    expect(
        Company::withTrashed()->find($companyId)->trashed()
    )->toBeTrue();
});


test('admin can soft delete a company', function () {

    $admin = createCompanyManagementAdmin();

    $owner = createCompanyTestUser();

    $company = createTestCompany($owner);

    $companyId = $company->id;

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/central/companies/{$companyId}");

    $response->assertStatus(200);

    expect(
        Company::withTrashed()->find($companyId)->trashed()
    )->toBeTrue();
});


test('user cannot soft delete another user company', function () {

    $user = createCompanyTestUser();

    $owner = createCompanyTestUser();

    $company = createTestCompany($owner);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/central/companies/{$company->id}");

    $response->assertStatus(403);
});


/*
|--------------------------------------------------------------------------
| Trashed Companies
|--------------------------------------------------------------------------
*/

test('admin can get all trashed companies', function () {

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $company->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/central/companies/trashed');

    $response->assertStatus(200);
});


test('admin can get a specific trashed company', function () {

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $company->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/central/companies/{$company->id}/trashed");

    $response->assertStatus(200);
});


test('cannot get a non trashed company through trashed endpoint', function () {

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/central/companies/{$company->id}/trashed");

    $response->assertStatus(404);
});


/*
|--------------------------------------------------------------------------
| Restore
|--------------------------------------------------------------------------
*/

test('admin can restore a trashed company', function () {

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $companyId = $company->id;

    $company->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/central/companies/{$companyId}/restore");

    $response->assertStatus(200);

    expect(
        Company::find($companyId)
    )->not->toBeNull();
});


test('cannot restore a non trashed company', function () {

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/central/companies/{$company->id}/restore");

    $response->assertStatus(404);
});


/*
|--------------------------------------------------------------------------
| Restore All
|--------------------------------------------------------------------------
*/

test('admin can restore all trashed companies', function () {

    $admin = createCompanyManagementAdmin();

    $company1 = createTestCompany($admin);
    $company2 = createTestCompany($admin);

    $company1->delete();
    $company2->delete();

    $company1Id = $company1->id;
    $company2Id = $company2->id;

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/central/companies/restore-all');

    $response->assertStatus(200);

    expect(Company::find($company1Id))
        ->not->toBeNull()
        ->and(Company::find($company2Id))
        ->not->toBeNull();
});


test('cannot restore all companies when there are no trashed companies', function () {

    $admin = createCompanyManagementAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/central/companies/restore-all');

    $response->assertStatus(404);
});


/*
|--------------------------------------------------------------------------
| Force Delete
|--------------------------------------------------------------------------
*/

test('admin can force delete a trashed company', function () {

    Queue::fake();

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $companyId = $company->id;

    $company->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/central/companies/{$companyId}/force-delete");

    $response->assertStatus(202);

    Queue::assertPushed(
        DeleteCompanyJob::class,
        function ($job) use ($companyId) {
            return $job->companyId === $companyId;
        }
    );

    expect(
        Company::withTrashed()->find($companyId)
    )->not->toBeNull();
});


test('cannot force delete a non trashed company', function () {

    Queue::fake();

    $admin = createCompanyManagementAdmin();

    $company = createTestCompany($admin);

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/central/companies/{$company->id}/force-delete");

    $response->assertStatus(404);

    Queue::assertNothingPushed();
});


/*
|--------------------------------------------------------------------------
| Force Delete All
|--------------------------------------------------------------------------
*/

test('admin can force delete all trashed companies', function () {

    Queue::fake();

    $admin = createCompanyManagementAdmin();

    $company1 = createTestCompany($admin);
    $company2 = createTestCompany($admin);

    $company1->delete();
    $company2->delete();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/central/companies/force-delete-all');

    $response->assertStatus(202);

    Queue::assertPushed(
        DeleteCompanyJob::class,
        2
    );
});


test('cannot force delete all companies when there are no trashed companies', function () {

    Queue::fake();

    $admin = createCompanyManagementAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/central/companies/force-delete-all');

    $response->assertStatus(404);

    Queue::assertNothingPushed();
});


/*
|--------------------------------------------------------------------------
| Restore / Force Delete Authorization
|--------------------------------------------------------------------------
*/

test('user without restore permission cannot restore company', function () {

    $user = createCompanyTestUser();

    $company = createTestCompany($user);

    $company->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/central/companies/{$company->id}/restore");

    $response->assertStatus(403);
});


test('user without force delete permission cannot force delete company', function () {

    Queue::fake();

    $user = createCompanyTestUser();

    $company = createTestCompany($user);

    $company->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/central/companies/{$company->id}/force-delete");

    $response->assertStatus(403);

    Queue::assertNothingPushed();
});


test('user without restore all permission cannot restore all companies', function () {

    $user = createCompanyTestUser();

    $company = createTestCompany($user);

    $company->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/central/companies/restore-all');

    $response->assertStatus(403);
});


test('user without force delete all permission cannot force delete all companies', function () {

    Queue::fake();

    $user = createCompanyTestUser();

    $company = createTestCompany($user);

    $company->delete();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/central/companies/force-delete-all');

    $response->assertStatus(403);

    Queue::assertNothingPushed();
});
