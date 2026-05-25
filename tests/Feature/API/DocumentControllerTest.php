<?php

uses()->group('api');

use App\Models\Document;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->apiUser = User::factory()->apiUser()->create();
    Passport::actingAs($this->apiUser);
});

test('index returns all documents', function () {
    Document::factory()->count(3)->create();

    $response = $this->getJson('/api/documents');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('index is forbidden for non-api users', function () {
    Passport::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/documents');

    $response->assertStatus(403);
});

test('store returns 501 not implemented', function () {
    $response = $this->postJson('/api/documents', []);

    $response->assertStatus(501);
});

test('show returns a single document', function () {
    $document = Document::factory()->create();

    $response = $this->getJson("/api/documents/{$document->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $document->id]);
});

test('update returns 500 not implemented', function () {
    $document = Document::factory()->create();

    $response = $this->putJson("/api/documents/{$document->id}", []);

    $response->assertStatus(500);
});

test('destroy deletes a document', function () {
    $document = Document::factory()->create();

    $response = $this->deleteJson("/api/documents/{$document->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
});

test('show returns 404 for nonexistent document', function () {
    $response = $this->getJson('/api/documents/9999');

    $response->assertStatus(404);
});
