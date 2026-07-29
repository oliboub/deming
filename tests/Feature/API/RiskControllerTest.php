<?php

uses()->group('api');

use App\Models\Action;
use App\Models\Control;
use App\Models\Risk;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->apiUser = User::factory()->apiUser()->create();
    Passport::actingAs($this->apiUser);
});

test('index returns all risks', function () {
    Risk::factory()->count(3)->create();

    $response = $this->getJson('/api/risks');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('index is forbidden for non-api users', function () {
    Passport::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/risks');

    $response->assertStatus(403);
});

test('store creates a risk', function () {
    $data = [
        'name' => 'Data leakage',
        'description' => 'Risk of sensitive data leakage',
        'probability' => 3,
        'impact' => 4,
        'status' => Risk::STATUS_NOT_EVALUATED,
        'review_frequency' => 12,
    ];

    $response = $this->postJson('/api/risks', $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['name' => 'Data leakage']);

    $this->assertDatabaseHas('risks', ['name' => 'Data leakage']);
});

test('store syncs controls and actions when provided', function () {
    $control = Control::factory()->create();
    $action = Action::factory()->create();

    $response = $this->postJson('/api/risks', [
        'name' => 'Risk with links',
        'probability' => 2,
        'impact' => 2,
        'status' => Risk::STATUS_NOT_EVALUATED,
        'review_frequency' => 12,
        'controls' => [$control->id],
        'actions' => [$action->id],
    ]);

    $response->assertStatus(201);

    $risk = Risk::where('name', 'Risk with links')->first();
    expect($risk->controls()->count())->toBe(1);
    expect($risk->actions()->count())->toBe(1);
});

test('show returns a single risk with controls and actions', function () {
    $risk = Risk::factory()->create();

    $response = $this->getJson("/api/risks/{$risk->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $risk->id])
        ->assertJsonStructure(['controls', 'actions']);
});

test('update modifies a risk', function () {
    $risk = Risk::factory()->create();

    $response = $this->putJson("/api/risks/{$risk->id}", [
        'name' => 'Updated risk',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('risks', ['id' => $risk->id, 'name' => 'Updated risk']);
});

test('destroy deletes a risk', function () {
    $control = Control::factory()->create();
    $risk = Risk::factory()->create();
    $risk->controls()->attach($control->id);

    $response = $this->deleteJson("/api/risks/{$risk->id}");

    $response->assertStatus(200);
    $this->assertSoftDeleted('risks', ['id' => $risk->id]);
});

test('show returns 404 for nonexistent risk', function () {
    $response = $this->getJson('/api/risks/9999');

    $response->assertStatus(404);
});
