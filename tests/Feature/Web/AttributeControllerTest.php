<?php

use App\Models\Attribute;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->user()->create();
});

test('guest is redirected to login', function () {
    $this->get('/attributes')->assertRedirect('/login');
});

test('admin can list attributes', function () {
    Attribute::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/attributes')->assertStatus(200);
});

test('user can list attributes', function () {
    $this->actingAs($this->user)->get('/attributes')->assertStatus(200);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/attributes/create')->assertStatus(200);
});

test('non-admin cannot access create form', function () {
    $this->actingAs($this->user)->get('/attributes/create')->assertStatus(403);
});

test('admin can create an attribute', function () {
    // values must match the #tag format required by AttributeController regex
    $this->actingAs($this->admin)
        ->post('/attributes', [
            'name' => 'classification',
            'values' => '#public #internal #confidential',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('attributes', ['name' => 'classification']);
});

test('non-admin cannot create an attribute', function () {
    $this->actingAs($this->user)
        ->post('/attributes', ['name' => 'test', 'values' => 'a,b'])
        ->assertStatus(403);
});

test('admin can view an attribute', function () {
    $attribute = Attribute::factory()->create();
    $this->actingAs($this->admin)->get("/attributes/{$attribute->id}")->assertStatus(200);
});

test('admin can edit an attribute', function () {
    $attribute = Attribute::factory()->create();
    $this->actingAs($this->admin)->get("/attributes/{$attribute->id}/edit")->assertStatus(200);
});

test('non-admin cannot edit an attribute', function () {
    $attribute = Attribute::factory()->create();
    $this->actingAs($this->user)->get("/attributes/{$attribute->id}/edit")->assertStatus(403);
});

test('admin can update an attribute', function () {
    $attribute = Attribute::factory()->create();

    $this->actingAs($this->admin)
        ->put("/attributes/{$attribute->id}", [
            'name' => 'updated_classification',
            'values' => '#low #medium #high',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('attributes', ['id' => $attribute->id, 'name' => 'updated_classification']);
});

test('admin can delete an attribute', function () {
    $attribute = Attribute::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/attributes/{$attribute->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
});

test('admin can export attributes', function () {
    Attribute::factory()->count(2)->create();
    $this->actingAs($this->admin)->get('/export/attributes')->assertStatus(200);
});
