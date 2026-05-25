<?php

use App\Models\Risk;
use App\Models\User;
use Database\Seeders\RiskScoringConfigSeeder;

beforeEach(function () {
    $this->admin   = User::factory()->admin()->create();
    $this->user    = User::factory()->create(['role' => User::ROLE_USER]);
    $this->auditor = User::factory()->auditor()->create();
    $this->seed(RiskScoringConfigSeeder::class);

});

test('guest is redirected to login', function () {
    $this->get('/risk/index')->assertRedirect('/login');
});

test('admin can list risks', function () {
    Risk::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/risk/index')->assertStatus(200);
});

test('user can list risks', function () {
    $this->actingAs($this->user)->get('/risk/index')->assertStatus(200);
});

test('auditor can list risks', function () {
    $this->actingAs($this->auditor)->get('/risk/index')->assertStatus(200);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/risk/create')->assertStatus(200);
});

test('admin can create a risk', function () {
    $this->actingAs($this->admin)
        ->post('/risk/store', [
            'name' => 'Unauthorized data access',
            'probability' => 3,
            'impact' => 4,
            'status' => Risk::STATUS_NOT_EVALUATED,
            'review_frequency' => 12,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('risks', ['name' => 'Unauthorized data access']);
});

test('user can create a risk', function () {
    $this->actingAs($this->user)
        ->post('/risk/store', [
            'name' => 'Data breach risk',
            'probability' => 2,
            'impact' => 5,
            'status' => Risk::STATUS_NOT_EVALUATED,
            'review_frequency' => 6,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('risks', ['name' => 'Data breach risk']);
});

test('admin can view any risk', function () {
    $risk = Risk::factory()->create();
    $this->actingAs($this->admin)->get("/risk/show/{$risk->id}")->assertStatus(200);
});

test('auditor can only view their own risk', function () {
    $risk = Risk::factory()->create(['owner_id' => $this->auditor->id]);
    $this->actingAs($this->auditor)->get("/risk/show/{$risk->id}")->assertStatus(200);
});

test('auditor cannot view someone else risk', function () {
    $risk = Risk::factory()->create(['owner_id' => $this->admin->id]);
    $this->actingAs($this->auditor)->get("/risk/show/{$risk->id}")->assertStatus(403);
});

test('admin can edit a risk', function () {
    $risk = Risk::factory()->create();
    $this->actingAs($this->admin)->get("/risk/edit/{$risk->id}")->assertStatus(200);
});

test('admin can update a risk', function () {
    $risk = Risk::factory()->create();

    $this->actingAs($this->admin)
        ->post('/risk/save', [
            'id' => $risk->id,
            'name' => 'Updated Risk Name',
            'probability' => 2,
            'impact' => 3,
            'status' => Risk::STATUS_MITIGATED,
            'review_frequency' => 12,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('risks', ['id' => $risk->id, 'name' => 'Updated Risk Name']);
});

test('admin can delete a risk', function () {
    $risk = Risk::factory()->create();

    $this->actingAs($this->admin)
        ->get("/risk/delete/{$risk->id}")
        ->assertRedirect('/risk/index');

    $this->assertSoftDeleted('risks', ['id' => $risk->id]);
});

test('non-admin cannot delete a risk', function () {
    $risk = Risk::factory()->create();
    $this->actingAs($this->user)->get("/risk/delete/{$risk->id}")->assertStatus(403);
});

test('admin can view risk matrix', function () {
    $this->actingAs($this->admin)->get('/risk/matrix')->assertStatus(200);
});

test('admin can export risks', function () {
    Risk::factory()->count(2)->create();
    $this->actingAs($this->admin)->get('/export/risks')->assertStatus(200);
});

test('auditor cannot export risks', function () {
    $this->actingAs($this->auditor)->get('/export/risks')->assertStatus(403);
});
