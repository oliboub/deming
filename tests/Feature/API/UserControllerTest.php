<?php

uses()->group('api');

use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->apiUser = User::factory()->apiUser()->create();
    Passport::actingAs($this->apiUser);
});

test('index returns all users', function () {
    User::factory()->count(3)->create();

    $response = $this->getJson('/api/users');

    // 3 created + 1 apiUser
    $response->assertStatus(200)
        ->assertJsonCount(4);
});

test('index is forbidden for non-api users', function () {
    Passport::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/users');

    $response->assertStatus(403);
});

test('store creates a user', function () {
    $data = [
        'login' => 'jdoe',
        'name' => 'John Doe',
        'title' => 'Security Analyst',
        'email' => 'jdoe@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_AUDITOR,
    ];

    $response = $this->postJson('/api/users', $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['login' => 'jdoe']);

    $this->assertDatabaseHas('users', ['login' => 'jdoe']);
});

test('show returns a single user', function () {
    $user = User::factory()->create();

    $response = $this->getJson("/api/users/{$user->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $user->id]);
});

test('update modifies a user', function () {
    $user = User::factory()->create();

    $response = $this->putJson("/api/users/{$user->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
});

test('destroy deletes a user', function () {
    $user = User::factory()->create();

    $response = $this->deleteJson("/api/users/{$user->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('show returns 404 for nonexistent user', function () {
    $response = $this->getJson('/api/users/9999');

    $response->assertStatus(404);
});
