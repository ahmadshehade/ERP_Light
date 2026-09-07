<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;

uses(DatabaseTransactions::class);

test('user can register', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'test name',
        'email' => '1@1.com',
        'password' => 'P@ssw0rd123',
        'password_confirmation' => 'P@ssw0rd123',
        'phone' => '+1234567890',
        'timezone' => 'Asian/Damascus',
        'language' => 'ar',
        'birth_date' => '2020-01-01',
    ]);

    $response->assertStatus(201);
});

test('user can login', function () {
    $user = User::create([
        'name' => 'Login Test User',
        'email' => 'login-test@example.com',
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login-test@example.com',
        'password' => 'P@ssw0rd123',
    ]);

    $response->assertStatus(200);
});

test('user can logout', function () {
    $user = User::create([
        'name' => 'Logout Test User',
        'email' => 'logout-test@example.com',
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200);
});

test('user cannot login with wrong password', function () {
    User::create([
        'name' => 'Wrong Password Test User',
        'email' => 'wrong-password-test@example.com',
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'wrong-password-test@example.com',
        'password' => 'WrongPassword123!',
    ]);

    $response->assertStatus(401);
});

test('user cannot login with non existing email', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'not-exist@example.com',
        'password' => 'P@ssw0rd123',
    ]);

    $response->assertStatus(422);
});

test('user cannot register with invalid password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Invalid Password User',
        'email' => 'invalid-password-test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '+1234567892',
        'timezone' => 'Asia/Damascus',
        'language' => 'ar',
        'birth_date' => '2020-01-01',
    ]);

    $response->assertStatus(422);
});

test('user cannot register with unconfirmed password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Unconfirmed Password User',
        'email' => 'unconfirmed-password@example.com',
        'password' => 'P@ssw0rd123',
        'password_confirmation' => 'DifferentPassword123!',
        'phone' => '+1234567893',
        'timezone' => 'Asia/Damascus',
        'language' => 'ar',
        'birth_date' => '2020-01-01',
    ]);

    $response->assertStatus(422);
});

test('user cannot register with existing email', function () {
    User::create([
        'name' => 'Existing User',
        'email' => 'existing-email@example.com',
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Another User',
        'email' => 'existing-email@example.com',
        'password' => 'P@ssw0rd123',
        'password_confirmation' => 'P@ssw0rd123',
        'phone' => '+1234567894',
        'timezone' => 'Asia/Damascus',
        'language' => 'ar',
        'birth_date' => '2020-01-01',
    ]);

    $response->assertStatus(422);
});

test('user cannot register with existing phone', function () {
    $user = User::create([
        'name' => 'Existing Phone User',
        'email' => 'existing-phone@example.com',
        'password' => Hash::make('P@ssw0rd123'),
    ]);

    $user->profile()->create([
        'phone' => '+1234567895',
        'timezone' => 'Asia/Damascus',
        'language' => 'ar',
        'birth_date' => '2020-01-01',
    ]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Another User',
        'email' => 'another-phone@example.com',
        'password' => 'P@ssw0rd123',
        'password_confirmation' => 'P@ssw0rd123',
        'phone' => '+1234567895',
        'timezone' => 'Asia/Damascus',
        'language' => 'ar',
        'birth_date' => '2020-01-01',
    ]);

    $response->assertStatus(422);
});

test('user cannot logout without authentication', function () {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertStatus(401);
});
