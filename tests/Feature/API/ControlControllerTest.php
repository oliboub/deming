<?php

uses()->group('api');

use App\Models\Control;
use App\Models\Domain;
use App\Models\Measure;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->apiUser = User::factory()->apiUser()->create();
    Passport::actingAs($this->apiUser);
});

test('index returns all controls', function () {
    Control::factory()->count(3)->create();

    $response = $this->getJson('/api/controls');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('index is forbidden for non-api users', function () {
    Passport::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/controls');

    $response->assertStatus(403);
});

test('store creates a control', function () {
    $domain = Domain::factory()->create();

    $data = [
        'domain_id' => $domain->id,
        'clause'    => '5.1.1',
        'name'      => 'Access Control Policy',
        'objective' => 'Ensure access is controlled',
    ];

    $response = $this->postJson('/api/controls', $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['name' => 'Access Control Policy']);

    $this->assertDatabaseHas('controls', ['clause' => '5.1.1']);
});

test('store syncs measures when provided', function () {
    $domain  = Domain::factory()->create();
    $measure = Measure::factory()->create();

    $response = $this->postJson('/api/controls', [
        'domain_id' => $domain->id,
        'clause'    => '5.1.2',
        'name'      => 'Control with Measure',
        'objective' => 'Test',
        'measures'  => [$measure->id],
    ]);

    $response->assertStatus(201);

    $control = Control::where('clause', '5.1.2')->first();
    expect($control->allMeasures()->count())->toBe(1);
});

test('show returns a single control with measures', function () {
    $control = Control::factory()->create();

    $response = $this->getJson("/api/controls/{$control->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $control->id])
        ->assertJsonStructure(['measures']);
});

test('update modifies a control', function () {
    $control = Control::factory()->create();

    $response = $this->putJson("/api/controls/{$control->id}", [
        'name'      => 'Updated Control',
        'objective' => 'Updated objective',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('controls', ['id' => $control->id, 'name' => 'Updated Control']);
});

test('destroy deletes a control', function () {
    $measure = Measure::factory()->create();
    $control = Control::factory()->create();
    $control->allMeasures()->attach($measure->id);

    $response = $this->deleteJson("/api/controls/{$control->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('controls', ['id' => $control->id]);
});

test('show returns 404 for nonexistent control', function () {
    $response = $this->getJson('/api/controls/9999');

    $response->assertStatus(404);
});
