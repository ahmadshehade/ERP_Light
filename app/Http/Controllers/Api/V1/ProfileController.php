<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Models\Profile;
use App\Services\ProfileService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    public ProfileService $profileService;

    use  AuthorizesRequests;
    /**
     * Summary of __construct
     * @prama ProfileService $profileService
     *
     */
    public  function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Summary of index
     * @prama Request $request
     * @return JsonResponse
     */
    public  function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Profile::class);
        $filters = $request->only(['user_id', 'phone', 'timezone', 'birth_date']);
        $profiles = $this->profileService->getAllProfiles($filters);
        return $this->successMessage('Profiles retrieved successfully', ['profiles' => $profiles], 200);
    }

    /**Summary of show
     * @prama Profile $profile
     * @return JsonResponse
     */
    public function show(Profile  $profile): JsonResponse
    {
        $this->authorize('view', $profile);
        $date = $this->profileService->getProfile($profile);
        return $this->successMessage('Profile retrieved successfully', ['profile' => $date], 200);
    }

    /**
     * Summary of update
     * @prama Profile $profile
     * @prama array $data
     *
     * @return JsonResponse
     */
    public function update(Profile $profile, UpdateProfileRequest $request): JsonResponse
    {
        $this->authorize('update', $profile);
        $date = $this->profileService->update($profile, $request->validated());
        return $this->successMessage('Profile updated successfully', ['profile' => $date], 200);
    }
}
