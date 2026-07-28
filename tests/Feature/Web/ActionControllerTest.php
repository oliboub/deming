<?php

use App\Models\Action;
use App\Models\Control;
use App\Models\User;

beforeEach(function () {
    $this->admin   = User::factory()->admin()->create();
    $this->user    = User::factory()->user()->create();
    $this->auditor = User::factory()->auditor()->create();
});

test('guest is redirected to login', function () {
    $this->get('/actions')->assertRedirect('/login');
});

test('admin can list actions', function () {
    Action::factory()->count(3)->create();
    $this->actingAs($this->admin)->get('/actions')->assertStatus(200);
});

test('auditor can list actions', function () {
    $this->actingAs($this->auditor)->get('/actions')->assertStatus(200);
});

test('api user cannot list actions', function () {
    $this->actingAs(User::factory()->apiUser()->create())
        ->get('/actions')
        ->assertStatus(403);
});

test('admin can access create form', function () {
    $this->actingAs($this->admin)->get('/action/create')->assertStatus(200);
});

test('auditor cannot access create form', function () {
    $this->actingAs($this->auditor)->get('/action/create')->assertStatus(403);
});

test('admin can create an action', function () {
    $this->actingAs($this->admin)
        ->post('/action/store', [
            'name' => 'Fix authentication vulnerability',
            'type' => '1',
            'due_date' => '2026-12-01',
        ])
        ->assertRedirect('/actions');

    $this->assertDatabaseHas('actions', ['name' => 'Fix authentication vulnerability']);
});

test('non-admin user can create an action', function () {
    $this->actingAs($this->user)
        ->post('/action/store', [
            'name' => 'Patch known vulnerability',
            'type' => '2',
        ])
        ->assertRedirect('/actions');

    $this->assertDatabaseHas('actions', ['name' => 'Patch known vulnerability']);
});

test('auditor cannot create an action', function () {
    $this->actingAs($this->auditor)
        ->post('/action/store', ['name' => 'Test Action'])
        ->assertStatus(403);
});

test('admin can view an action', function () {
    $action = Action::factory()->create();
    $this->actingAs($this->admin)->get("/action/show/{$action->id}")->assertStatus(200);
});

test('auditor can view an action', function () {
    $action = Action::factory()->create();
    $this->actingAs($this->auditor)->get("/action/show/{$action->id}")->assertStatus(200);
});

test('api user cannot view an action', function () {
    $action = Action::factory()->create();
    $this->actingAs(User::factory()->apiUser()->create())
        ->get("/action/show/{$action->id}")
        ->assertStatus(403);
});

test('admin can edit an action', function () {
    $action = Action::factory()->create();
    $this->actingAs($this->admin)->get("/action/edit/{$action->id}")->assertStatus(200);
});

test('auditor can access edit form', function () {
    $action = Action::factory()->create();
    $this->actingAs($this->auditor)->get("/action/edit/{$action->id}")->assertStatus(200);
});

test('admin can save an action', function () {
    $action = Action::factory()->create();

    $this->actingAs($this->admin)
        ->post('/action/save', [
            'id' => $action->id,
            'name' => 'Updated Action Name',
            'status' => 0,
        ])
        ->assertRedirect("/action/show/{$action->id}");

    $this->assertDatabaseHas('actions', ['id' => $action->id, 'name' => 'Updated Action Name']);
});

test('non-admin user can save an action', function () {
    $action = Action::factory()->create();

    $this->actingAs($this->user)
        ->post('/action/save', [
            'id' => $action->id,
            'name' => 'User Updated Action',
            'status' => 0,
        ])
        ->assertRedirect("/action/show/{$action->id}");
});

test('auditor cannot save an action', function () {
    $action = Action::factory()->create();
    $this->actingAs($this->auditor)
        ->post('/action/save', ['id' => $action->id, 'name' => 'Updated'])
        ->assertStatus(403);
});

test('admin can delete an action', function () {
    $action = Action::factory()->create();
    $this->actingAs($this->admin)
        ->post('/action/delete', ['id' => $action->id])
        ->assertRedirect();

    $this->assertDatabaseMissing('actions', ['id' => $action->id]);
});

test('auditor cannot delete an action', function () {
    $action = Action::factory()->create();
    $this->actingAs($this->auditor)
        ->post('/action/delete', ['id' => $action->id])
        ->assertStatus(403);
});

test('admin can export actions', function () {
    Action::factory()->count(2)->create();
    $this->actingAs($this->admin)->get('/export/actions')->assertStatus(200);
});
