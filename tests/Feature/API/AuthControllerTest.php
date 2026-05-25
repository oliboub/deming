<?php

uses()->group('api');

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

beforeEach(function () {
    Passport::$clientUuids = false;
    Artisan::call('passport:install', ['--no-interaction' => true]);
});

test('login with valid credentials returns token', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret123'),
        'role' => User::ROLE_API,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token']);
});

test('login with invalid credentials returns 401', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthorised.']);
});

test('logout revokes tokens', function () {
    $user = User::factory()->apiUser()->create();
    Passport::actingAs($user);

    $response = $this->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Successfully logged out']);
});

test('accessing protected route without auth returns 401', function () {
    $response = $this->getJson('/api/domains');

    $response->assertStatus(401);
});
