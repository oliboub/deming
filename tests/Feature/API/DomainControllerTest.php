<?php

uses()->group('api');

use App\Models\Domain;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->apiUser = User::factory()->apiUser()->create();
    Passport::actingAs($this->apiUser);
});

test('index returns all domains', function () {
    Domain::factory()->count(3)->create();

    $response = $this->getJson('/api/domains');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('index is forbidden for non-api users', function () {
    $user = User::factory()->admin()->create();
    Passport::actingAs($user);

    $response = $this->getJson('/api/domains');

    $response->assertStatus(403);
});

test('store creates a domain', function () {
    $data = [
        'title' => 'Security Policies',
        'framework' => 'ISO27001',
        'description' => 'Security policies domain',
    ];

    $response = $this->postJson('/api/domains', $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['title' => 'Security Policies']);

    $this->assertDatabaseHas('domains', ['title' => 'Security Policies']);
});

test('show returns a single domain', function () {
    $domain = Domain::factory()->create();

    $response = $this->getJson("/api/domains/{$domain->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $domain->id]);
});

test('update modifies a domain', function () {
    $domain = Domain::factory()->create();

    $response = $this->putJson("/api/domains/{$domain->id}", [
        'title' => 'Updated Title',
        'framework' => $domain->framework,
        'description' => $domain->description,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('domains', ['id' => $domain->id, 'title' => 'Updated Title']);
});

test('destroy deletes a domain', function () {
    $domain = Domain::factory()->create();

    $response = $this->deleteJson("/api/domains/{$domain->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
});

test('show returns 404 for nonexistent domain', function () {
    $response = $this->getJson('/api/domains/9999');

    $response->assertStatus(404);
});
