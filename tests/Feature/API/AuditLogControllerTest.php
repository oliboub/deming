<?php

uses()->group('api');

use App\Models\AuditLog;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->apiUser = User::factory()->apiUser()->create();
    AuditLog::truncate();
    Passport::actingAs($this->apiUser);
});

test('index returns all audit logs', function () {
    AuditLog::factory()->count(3)->create(['user_id' => $this->apiUser->id]);

    $response = $this->getJson('/api/logs');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('index is forbidden for non-api users', function () {
    Passport::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/logs');

    $response->assertStatus(403);
});

test('show returns a single audit log', function () {
    $log = AuditLog::factory()->create(['user_id' => $this->apiUser->id]);

    $response = $this->getJson("/api/logs/{$log->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $log->id]);
});

test('show is forbidden for non-api users', function () {
    $log = AuditLog::factory()->create(['user_id' => $this->apiUser->id]);
    Passport::actingAs(User::factory()->admin()->create());

    $response = $this->getJson("/api/logs/{$log->id}");

    $response->assertStatus(403);
});

test('store is always forbidden', function () {
    $response = $this->postJson('/api/logs', []);

    $response->assertStatus(403);
});

test('update is always unauthorized', function () {
    $log = AuditLog::factory()->create(['user_id' => $this->apiUser->id]);

    $response = $this->putJson("/api/logs/{$log->id}", []);

    $response->assertStatus(401);
});

test('destroy is always unauthorized', function () {
    $log = AuditLog::factory()->create(['user_id' => $this->apiUser->id]);

    $response = $this->deleteJson("/api/logs/{$log->id}");

    $response->assertStatus(401);
});
