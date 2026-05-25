<?php

uses()->group('api');

use App\Models\Attribute;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
    // AttributeController requires role === 4 (ROLE_API)
    $this->apiUser = User::factory()->apiUser()->create();
    Passport::actingAs($this->apiUser);
});

test('index returns all attributes', function () {
    Attribute::factory()->count(3)->create();

    $response = $this->getJson('/api/attributes');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('index is forbidden for non-api users', function () {
    Passport::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/attributes');

    $response->assertStatus(403);
});

test('store creates an attribute', function () {
    $data = [
        'name' => 'classification',
        'values' => 'public,internal,confidential,secret',
    ];

    $response = $this->postJson('/api/attributes', $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['name' => 'classification']);

    $this->assertDatabaseHas('attributes', ['name' => 'classification']);
});

test('show returns a single attribute', function () {
    $attribute = Attribute::factory()->create();

    $response = $this->getJson("/api/attributes/{$attribute->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $attribute->id]);
});

test('update modifies an attribute', function () {
    $attribute = Attribute::factory()->create();

    $response = $this->putJson("/api/attributes/{$attribute->id}", [
        'name' => 'updated_name',
        'values' => 'val1,val2',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('attributes', ['id' => $attribute->id, 'name' => 'updated_name']);
});

test('destroy deletes an attribute', function () {
    $attribute = Attribute::factory()->create();

    $response = $this->deleteJson("/api/attributes/{$attribute->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
});

test('show returns 404 for nonexistent attribute', function () {
    $response = $this->getJson('/api/attributes/9999');

    $response->assertStatus(404);
});
