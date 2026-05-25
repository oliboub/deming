<?php

uses()->group('api');

use App\Models\Control;
use App\Models\Measure;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->apiUser = User::factory()->apiUser()->create();
    Passport::actingAs($this->apiUser);
});

test('index returns all measures', function () {
    Measure::factory()->count(3)->create();

    $response = $this->getJson('/api/measures');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('index is forbidden for non-api users', function () {
    Passport::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/measures');

    $response->assertStatus(403);
});

test('store creates a measure', function () {
    $data = [
        'name'        => 'Quarterly Access Review',
        'objective'   => 'Review access rights quarterly',
        'plan_date'   => '2026-06-01',
        'periodicity' => 3,
    ];

    $response = $this->postJson('/api/measures', $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['name' => 'Quarterly Access Review']);

    $this->assertDatabaseHas('measures', ['name' => 'Quarterly Access Review']);
});

test('show returns a single measure with controls', function () {
    $measure = Measure::factory()->create();

    $response = $this->getJson("/api/measures/{$measure->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $measure->id])
        ->assertJsonStructure(['controls']);
});

test('update modifies a measure', function () {
    $measure = Measure::factory()->create();

    $response = $this->putJson("/api/measures/{$measure->id}", [
        'name' => 'Updated Measure Name',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('measures', ['id' => $measure->id, 'name' => 'Updated Measure Name']);
});

test('store syncs controls when provided', function () {
    $control = Control::factory()->create();

    $response = $this->postJson('/api/measures', [
        'name'      => 'Measure with Control',
        'plan_date' => '2026-06-01',
        'controls'  => [$control->id],
    ]);

    $response->assertStatus(201);

    $measure = Measure::where('name', 'Measure with Control')->first();
    expect($measure->controls()->count())->toBe(1);
});

test('destroy deletes a measure', function () {
    $measure = Measure::factory()->create();

    $response = $this->deleteJson("/api/measures/{$measure->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('measures', ['id' => $measure->id]);
});

test('show returns 404 for nonexistent measure', function () {
    $response = $this->getJson('/api/measures/9999');

    $response->assertStatus(404);
});
