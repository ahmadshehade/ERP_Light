<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterUserRequest;
use App\Services\Auth\AuthService;
use App\Http\Requests\Api\V1\Auth\LoginUserRequest;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    public  AuthService $authService;

    /**
     * Create a new AuthController instance.
     */
    public  function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }


    /**
     * Register a new user
     */
    public  function register(RegisterUserRequest $request)
    {
        $data = $this->authService->register($request->validated());
        return $this->successMessage('User created successfully', $data, 201);
    }

    /**
     * Login user and generate token
     *
     */
    public  function login(LoginUserRequest $request)
    {
        $data = $this->authService->login($request->validated());
        return $this->successMessage('User logged in successfully', $data, 200);
    }

    /**
     * Logout user (delete all tokens)
     *
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $this->authService->logout($user);
        return $this->successMessage('User logged out successfully', [], 200);
    }
}
